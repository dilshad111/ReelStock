# Product Engineering & Job Card Master Tutorial

## Purpose

Use **Product Engineering & Job Card Master** to define the permanent technical master of a carton product. This is not a production order. Production orders will later use this master to load components, BOM, routing, and process instructions automatically.

Example products:

- Single RSC carton with one component
- Shoe box with Top Lid and Bottom Tray
- Fruit carton with Main Carton, Partition, and Pad Sheet

## Opening The Module

Go to:

`Production > Product Engineering`

The screen has:

- Product master list
- Search and filters
- Product structure tree
- Components tab
- BOM and routing tab
- Revision history tab
- Production explosion preview
- New/Edit product master form

## Product Master Fields

### Product Code

Enter the permanent product code used by the company.

Use a short unique code.

Examples:

- `PRD-MC-0001`
- `GETZ-MC-4`
- `SHOE-BOX-001`

Do not use production job numbers here. This code should remain stable even when the product is revised.

### Product Name

Enter the product or carton name.

Examples:

- `Master Cartons M.C-4`
- `Shoe Box Large`
- `Fruit Carton Export Pack`

### Customer

Select the customer if this product belongs to a specific customer.

Leave blank for general products that can be used for multiple customers.

### Product Category

Select or type the product category.

Examples:

- `RSC Carton`
- `Top & Bottom Carton`
- `Separator`
- `Honeycomb`
- `Display Carton`

### Revision Number

Enter the current revision number of the product definition.

Examples:

- `1` for the first version
- `2` after the first engineering revision
- `3` after another approved change

### Status

Select whether the product master is usable.

- `Active`: available for production planning
- `Inactive`: kept for record but not normally used

### Revision Date

Enter the date this revision became effective.

Use the date when the specification was approved or changed.

### Create New Revision

This appears when editing an existing product master.

Turn this on when the change is a real customer or engineering revision, such as:

- Size changed
- Ply changed
- Paper construction changed
- Printing details changed
- Routing changed

Keep it off when correcting a data-entry mistake.

### Remarks

Enter permanent notes about the product.

Examples:

- `Customer requires export quality board.`
- `Use only approved supplier papers.`
- `Keep artwork centered on length panel.`

### Change Notes

Enter why this record or revision is being saved.

Examples:

- `Initial product engineering master created.`
- `Customer changed board from 3 Ply to 5 Ply.`
- `Printing changed from 2 color to 3 color.`

## Component Management Fields

A product must have at least one component.

### Component Code

Enter a unique code for the component inside the product.

Examples:

- `CMP-001`
- `TOP-LID`
- `BOTTOM-TRAY`
- `PARTITION-01`

### Component Name

Enter the component name.

Examples:

- `Main Carton`
- `Top Lid`
- `Bottom Tray`
- `Partition`
- `Pad Sheet`

### Quantity Per Product

Enter how many pieces of this component are required for one finished product.

Examples:

- Single carton: `1`
- Shoe box top lid: `1`
- Shoe box bottom tray: `1`
- Fruit carton partition: `2`

If production order quantity is `5,000` and component quantity per product is `2`, then required component quantity will be `10,000`.

### Component Type

Select or type the component type.

Examples:

- `Main Carton`
- `Top Lid`
- `Bottom Tray`
- `Partition`
- `Pad Sheet`
- `Separator`

### Active

Turn on if this component is currently used.

Turn off if the component is preserved for history but not used in current production.

## Component Specification Fields

Each component has its own dimensions and manufacturing specifications.

### Length

Enter component length.

Example:

- `320`

### Width

Enter component width.

Example:

- `260`

### Height

Enter component height.

For flat sheets, separators, or pads, this can be `0` or left blank if not applicable.

Example:

- `180`

### UOM

Select the unit of measure.

Options:

- `mm`
- `inch`
- `cm`

Use one unit consistently for the component.

### Ply Type

Select the board structure.

Options:

- `3 Ply`
- `5 Ply`
- `7 Ply`
- `Custom`

When 3/5/7 Ply is selected, the system can generate standard paper BOM rows.

### Flute Type

Select or type the flute used in this component.

Examples:

- `B-Flute`
- `C-Flute`
- `E-Flute`
- `B+C Flute`

### Board Grade

Enter the board grade or construction description.

Examples:

- `3 Ply B Flute 150/120/150`
- `5 Ply B+C 150/120/125/120/150`
- `Export Kraft 5 Ply`

### Joint Type

Select how the carton is joined.

Examples:

- `N/A`
- `Glue`
- `Stitching`
- `Tape`

### Printed

Select whether this component has printing.

- `Yes`: component is printed
- `No`: component is plain/unprinted

### Printing Colors

Enter number of print colors.

Examples:

- `0` for unprinted
- `1` for one-color printing
- `2` for two-color printing
- `4` for four-color printing

### Bundle Quantity

Enter how many finished components/cartons are packed in one bundle.

Examples:

- `10`
- `20`
- `25`
- `50`

### Special Instructions

Enter component-specific production instructions.

Examples:

- `Maintain flute direction along length.`
- `Do not over-crease lid corners.`
- `Check print registration after every 500 sheets.`

## Board Construction / Paper BOM Fields

Use this section to define the paper layers for each component.

### Layer Sequence

Enter the layer order.

Examples:

- `1` Top Layer
- `2` Flute-B
- `3` Inner Layer

### Paper Type

Enter the paper or layer name.

Examples:

- `Top Layer`
- `Flute-B`
- `Middle Layer`
- `Flute-C`
- `Inner Layer`
- `Local Kraft Liner`
- `Imported Test Liner`

### GSM

Enter paper GSM for the layer.

Examples:

- `110`
- `125`
- `150`
- `180`

### Supplier

Select supplier if the layer must use paper from a preferred supplier.

Leave blank if any approved supplier is allowed.

## Standard BOM Examples

### 3 Ply

| Sequence | Paper Type | Example GSM |
|---|---|---|
| 1 | Top Layer | 150 |
| 2 | Flute-B | 120 |
| 3 | Inner Layer | 150 |

### 5 Ply

| Sequence | Paper Type | Example GSM |
|---|---|---|
| 1 | Top Layer | 150 |
| 2 | Flute-B | 120 |
| 3 | Middle Layer | 125 |
| 4 | Flute-C | 120 |
| 5 | Inner Layer | 150 |

### 7 Ply

| Sequence | Paper Type | Example GSM |
|---|---|---|
| 1 | Top Layer | 150 |
| 2 | Flute-B | 120 |
| 3 | Middle Layer 1 | 125 |
| 4 | Flute-C | 120 |
| 5 | Middle Layer 2 | 125 |
| 6 | Flute-B | 120 |
| 7 | Inner Layer | 150 |

## Manufacturing Routing Fields

Routing defines the production sequence for each component.

### Sequence

Enter the step number.

Examples:

- `1` Corrugation
- `2` Printing
- `3` Die Cutting
- `4` Gluing
- `5` Packing

### Process Name

Select or type the production process.

Examples:

- `Corrugation`
- `Printing`
- `Slotting`
- `Die Cutting`
- `Gluing`
- `Stitching`
- `Packing`
- `Lamination`
- `UV Coating`
- `Window Pasting`
- `Foil Stamping`
- `Embossing`

### Process Order

Enter the order in which this process should run.

Usually this is the same as Sequence.

### Active

Turn on if this process is used.

Turn off if this step is kept for reference but should not be loaded into production orders.

### Process Instructions

Enter detailed instructions for the selected process.

Examples:

- Corrugation: `Use 47 inch reel, maintain sheet length 48.62 inch.`
- Printing: `Print outside, logo centered, 2 color flexo.`
- Die Cutting: `Use die D-1007, check slot dimensions.`
- Gluing: `Apply adhesive on manufacturer joint only.`
- Packing: `Bundle 20 cartons, label each bundle.`

## Unlimited Process Parameters

Use this area for process-specific values without changing the database.

Each row has:

- Parameter Name
- Value

### Corrugation Parameter Examples

| Parameter | Value |
|---|---|
| Reel Width | 47 inch |
| Sheet Width | 43 inch |
| Sheet Length | 48.62 inch |
| Online Slitting | Yes |
| Number of Outs | 2 |
| Trim Allowance | 12 mm |

### Printing Parameter Examples

| Parameter | Value |
|---|---|
| Artwork Code | ART-GETZ-001 |
| Number of Colors | 2 |
| Print Side | Outside |
| Print Position | Center panel |

### Die Cutting Parameter Examples

| Parameter | Value |
|---|---|
| Die Number | D-1007 |
| Crease Layout | Standard |
| Slot Dimensions | 25 x 180 mm |

### Gluing/Stitching Parameter Examples

| Parameter | Value |
|---|---|
| Joint Width | 35 mm |
| Stitch Count | 6 |
| Glue Type | Cold adhesive |

### Packing Parameter Examples

| Parameter | Value |
|---|---|
| Bundle Quantity | 20 |
| Pallet Quantity | 500 |
| Label Format | Customer label |

## Revision History

Revision History stores past versions of the product master.

Use revisions when product specifications change permanently.

Examples:

- Size changed from `320 x 260 x 180` to `330 x 260 x 180`
- 3 Ply changed to 5 Ply
- Printing colors changed from 2 to 3
- Routing changed from Slotting to Die Cutting

## Production Explosion Preview

Use this tab to test how components will be calculated when a production order is created.

Example:

Product quantity: `5,000`

| Component | Qty Per Product | Required Qty |
|---|---:|---:|
| Top Lid | 1 | 5,000 |
| Bottom Tray | 1 | 5,000 |
| Partition | 2 | 10,000 |

This helps verify that component quantities are correct before production order integration.

## Recommended Workflow

1. Create Product Master.
2. Add one or more components.
3. Fill component specifications.
4. Generate or enter BOM layers.
5. Add routing processes in correct sequence.
6. Add process parameters and instructions.
7. Save the master.
8. Review the Product Structure Tree.
9. Test Production Explosion with a sample quantity.
10. Use revision creation only for real specification changes.

## Data Entry Tips

- Use stable Product Codes and Component Codes.
- Keep Product Master separate from Production Order.
- Use one component for simple cartons.
- Use multiple components for sets like top/bottom boxes or cartons with partitions.
- Always maintain BOM sequence correctly.
- Use process parameters for flexible process-specific details.
- Keep old versions through Revision History instead of overwriting real engineering changes.
