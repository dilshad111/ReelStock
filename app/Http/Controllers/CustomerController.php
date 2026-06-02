<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\ShippingAddress;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index()
    {
        return response()->json(Customer::with('shippingAddresses')->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'shipping_addresses' => 'nullable|array',
            'shipping_addresses.*.id' => 'nullable|integer|exists:shipping_addresses,id',
            'shipping_addresses.*.address_name' => 'required|string',
            'shipping_addresses.*.full_address' => 'required|string',
            'shipping_addresses.*.round_trip_distance_km' => 'nullable|numeric|min:0',
            'shipping_addresses.*.contact_person' => 'nullable|string',
            'shipping_addresses.*.phone' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $customer = Customer::create($request->only(['name', 'email', 'phone', 'address']));

            if ($request->has('shipping_addresses')) {
                foreach ($request->shipping_addresses as $addr) {
                    $customer->shippingAddresses()->create($addr);
                }
            }

            return response()->json($customer->load('shippingAddresses'), 201);
        });
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'shipping_addresses' => 'nullable|array',
            'shipping_addresses.*.id' => 'nullable|integer|exists:shipping_addresses,id',
            'shipping_addresses.*.address_name' => 'required|string',
            'shipping_addresses.*.full_address' => 'required|string',
            'shipping_addresses.*.round_trip_distance_km' => 'nullable|numeric|min:0',
            'shipping_addresses.*.contact_person' => 'nullable|string',
            'shipping_addresses.*.phone' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $customer) {
            $customer->update($request->only(['name', 'email', 'phone', 'address']));

            if ($request->has('shipping_addresses')) {
                $this->syncShippingAddresses($customer, $request->input('shipping_addresses', []));
            }

            return response()->json($customer->load('shippingAddresses'));
        });
    }

    public function destroy($id)
    {
        $customer = Customer::with('shippingAddresses')->findOrFail($id);

        if ($customer->shippingAddresses->contains(fn ($address) => $address->hasTransportHistory())) {
            abort(409, 'This customer has cartage history. Delete or revise the related cartage records before deleting the customer.');
        }

        $customer->delete();
        return response()->json(['message' => 'Customer deleted']);
    }

    public function storeAddress(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $request->validate([
            'address_name' => 'required|string',
            'full_address' => 'required|string',
            'round_trip_distance_km' => 'nullable|numeric|min:0',
            'contact_person' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        return response()->json(
            $customer->shippingAddresses()->create($this->addressData($request->all())),
            201
        );
    }

    public function updateAddress(Request $request, $addressId)
    {
        $address = ShippingAddress::findOrFail($addressId);

        $request->validate([
            'address_name' => 'required|string',
            'full_address' => 'required|string',
            'round_trip_distance_km' => 'nullable|numeric|min:0',
            'contact_person' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $address->update($this->addressData($request->all()));

        return response()->json($address);
    }

    public function destroyAddress($addressId)
    {
        $address = ShippingAddress::findOrFail($addressId);

        $this->ensureAddressCanBeDeleted(collect([$address]));
        $address->delete();

        return response()->json(['message' => 'Shipping address deleted']);
    }

    private function syncShippingAddresses(Customer $customer, array $addresses): void
    {
        $submittedAddressIds = [];

        foreach ($addresses as $addressData) {
            if (!empty($addressData['id'])) {
                $address = $customer->shippingAddresses()->whereKey($addressData['id'])->first();

                if (!$address) {
                    abort(422, 'One of the shipping addresses does not belong to this customer.');
                }

                $address->update($this->addressData($addressData));
                $submittedAddressIds[] = $address->id;
                continue;
            }

            $address = $customer->shippingAddresses()->create($this->addressData($addressData));
            $submittedAddressIds[] = $address->id;
        }

        $addressesToDelete = $customer->shippingAddresses()
            ->whereNotIn('id', $submittedAddressIds)
            ->get();

        $this->ensureAddressCanBeDeleted($addressesToDelete);

        $addressesToDelete->each->delete();
    }

    private function addressData(array $address): array
    {
        return [
            'address_name' => $address['address_name'],
            'full_address' => $address['full_address'],
            'round_trip_distance_km' => $address['round_trip_distance_km'] ?? null,
            'contact_person' => $address['contact_person'] ?? null,
            'phone' => $address['phone'] ?? null,
        ];
    }

    private function ensureAddressCanBeDeleted($addresses): void
    {
        $blockedAddresses = $addresses->filter(fn ($address) => $address->hasTransportHistory());

        if ($blockedAddresses->isNotEmpty()) {
            $names = $blockedAddresses->pluck('address_name')->implode(', ');
            abort(409, "Cannot remove shipping address with cartage history: {$names}.");
        }
    }
}
