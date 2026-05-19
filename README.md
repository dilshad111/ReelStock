# ReelStock - Paper Reel & Inventory Management System

ReelStock is a professional-grade web application designed specifically for carton manufacturing facilities. It provides a comprehensive solution for tracking paper reels, managing quality control, optimizing transport logistics, and monitoring finished goods inventory.

## 🚀 Key Features

### 📦 Reel Management
- **Intake & Receipt:** Track incoming reels with auto-generated serial numbers and lot tracking.
- **Consumption Tracking:** Real-time weight synchronization as reels are issued to production.
- **Balance Management:** Automated calculation of partially used reels and stock availability.

### 🧪 Quality Control
- **Inspection Logs:** Record GSM, Bursting Strength, Moisture, and Cob test results.
- **Status Workflows:** Approved, Rejected, and On-Hold statuses for incoming materials.

### 🚛 Transport & Logistics
- **Cartage Billing:** Manage transport rates per customer and vehicle type.
- **Rate Increments:** Track historical fuel/service rate adjustments.
- **Vehicle Tracking:** Maintain a database of transporters and vehicles.

### 🏭 Finished Goods
- **Job-Based Tracking:** Track produced cartons against specific jobs.
- **Dispatch Management:** Streamlined workflow for shipping finished products to customers.

### 📊 Intelligence & Reporting
- **Predictive Analytics:** Estimate future paper consumption based on historical trends.
- **Audit Logs:** Full history of every data change for accountability.
- **Inventory Alerts:** Automated notifications for low stock levels.

## 🛠 Tech Stack

- **Backend:** Laravel 9 (PHP 8.2)
- **Frontend:** Vue 3, Element Plus, Bootstrap 5
- **Database:** MySQL
- **Build System:** Vite

## ⚙️ Installation

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd reelStock
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup:**
   - Copy `.env.example` to `.env`
   - Configure your database settings.
   - Run `php artisan key:generate`

4. **Database Migration:**
   ```bash
   php artisan migrate --seed
   ```

5. **Build & Run:**
   ```bash
   npm run dev
   ```

## 📄 Documentation

For full technical documentation, architecture diagrams, and API references, please see the [System Documentation](file:///C:/Users/HP/.gemini/antigravity/brain/f2ba5333-d9be-42f7-9293-189f2dea088b/system_documentation.md).

## 🛡 License

This project is proprietary and confidential. Unauthorized copying or distribution is prohibited.
