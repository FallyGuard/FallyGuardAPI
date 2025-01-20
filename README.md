# Fall Detection 🚑  
A Laravel-based application designed to detect falls in real-time and notify caregivers or emergency contacts. This project aims to enhance safety for elderly individuals, patients, or people with mobility issues.

## 🛠 Features  
✅ **Real-time Fall Detection** – Fall detection is real-time, caregiver can see all falling around him in his location.
✅ **Instant Alerts** – Notifies emergency contacts via SMS, email, or push notifications.  
✅ **User Management** – Admins can register users, add emergency contacts, and manage roles.  
✅ **Activity Logging** – Stores fall incidents for analysis and medical history.  
✅ **Dashboard & Reports** – Interactive UI for monitoring user status and incident history.  
✅ **API Support** – RESTful API for mobile app or third-party integrations.  

## 🏗 Tech Stack  
- **Backend:** Laravel (PHP)  
- **Database:** MySQL
- **Frontend:** Flutter in Mobile, Angular in Frontend  
- **Notifications:** Pusher Channels, Whatsapp SMS, Mobile SMS Vonage, and Email (SMTP)  
- **Authentication:** Laravel Breeze/Sanctum  
- **Deployment:** Shared Server with a namecheap domain.  

## 🚀 Installation Guide  
1. Clone the repository:  
   ```sh
   git clone https://github.com/yourusername/fall-detection.git
   cd fall-detection
   ```
2. Install dependencies:  
   ```sh
   composer install
   npm install
   ```
3. Set up the `.env` file:  
   ```sh
   cp .env.example .env
   php artisan key:generate
   ```
4. Configure the database:  
   ```sh
   php artisan migrate --seed
   ```
5. Start the development server:  
   ```sh
   php artisan serve
   npm run dev
   ```

## 📡 API Documentation
https://documenter.getpostman.com/view/23054100/2sA2rGtJj7

## 📸 Screenshots  
![Dashboard Preview](![image](https://github.com/user-attachments/assets/e96a1bdd-a30d-49af-8d04-0eefd885578d))
![image](https://github.com/user-attachments/assets/fba5a1ac-d134-47f4-8a61-84a5f1dfeefc)
![image](https://github.com/user-attachments/assets/b544b5ea-24cc-4d96-8eb4-825116031288)
![image](https://github.com/user-attachments/assets/6e64f482-024f-47a7-9a72-bf324d1be476)


## 🤝 Contributing  
Want to contribute? Please follow these steps:  
1. Fork the repo  
2. Create a feature branch (`git checkout -b feature-name`)  
3. Commit changes (`git commit -m "Add feature"`)  
4. Push to branch (`git push origin feature-name`)  
5. Open a pull request  

## 📜 License  
This project is licensed under the **MIT License**.
