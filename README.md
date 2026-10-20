<p align="center"><img src="public/images/vonage-logo-black.png" width="400" alt="Vonage, part of Ericsson"></p>

# Vonage Reports API Sample Application

To run this demo, follow these steps:

1. Install the PHP dependencies:

   ```bash
   composer install
   ```

2. Copy the example environment file and generate an application key:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Add your Vonage credentials to `.env`:

   ```dotenv
   VONAGE_API_KEY=your-api-key
   VONAGE_API_SECRET=your-api-secret
   VONAGE_ACCOUNT_ID=your-account-id
   ```

4. Run the database migrations and seed the database:

   ```bash
   php artisan migrate --seed
   ```

5. Install and build the frontend assets:

   ```bash
   npm install
   npm run build
   ```

6. Pull records from the Vonage Reports API (defaults to the last 24 hours for all products):

   ```bash
   php artisan vonage:sync-reports
   ```

7. Start the application:

   ```bash
   composer run dev
   ```

8. Open the dashboard in your browser and log in with the seeded user:
   - **Email:** `test@example.com`
   - **Password:** `password`
