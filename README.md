# Covid-19 Vaccination Program by Farhan Israq
This program is designed with performance and scalability in mind, focusing on delivering a smooth user experience even under high traffic.

## Screenshots

![Homepage Screenshot](/public/screenshot-main.png)
<p align="center" style="font-weight:500;">Homepage</p>

| ![Registration Page](/public/screenshot-registration-page.png) | ![Search Page](/public/screenshot-search-page.png) | ![Search Result](/public/screenshot-search-result.png) |
|:--------------------------------------------:|:----------------------------------------:|:-----------------------------------------:|
| **Registration Page**                        | **Search Page**                          | **Search Result**                         |

## Installation Guide

Follow these steps to set up and run the project:

1. Clone the repository:
    ```bash
    git clone git@github.com:FarhanShares/covid-19-vaccination.git
    ```

2. Navigate to the project directory (`cd <project_directory>`) and copy the `.env.example` to `.env`:
    ```bash
    cp .env.example .env
    ```
    
3. Ensure Laravel 11 compatible PHP & Composer are installed and install the Composer dependencies
   ```bash
    composer install
    ```
   
4. Ensure Docker is running with Docker Compose installed.

5. Start the application using Laravel Sail:
    ```bash
    ./vendor/bin/sail up -d
    ```
    
6. Install the NPM dependencies.
    ```bash
    ./vendor/bin/sail npm install
    ```
   
7. Build frontend assets.
  
   ```bash
    ./vendor/bin/sail npm run build
    ```
  
7. Migration, Seeding and Initialization: The command will migrate, seed and optimize the app at once
   ```bash
   ./vendor/bin/sail php artisan app:init
   ```

8. Once the Docker build completes, run the following in separate terminal sessions:
    - First session (for queue worker):
      ```bash
      ./vendor/bin/sail php artisan queue:work
      ```
    - Second session (for scheduler):
      ```bash
      ./vendor/bin/sail php artisan schedule:work
      ```

Now the project is ready, and you can start interacting with it! 

* Homepage: http://localhost
* Mailpit (Email notification): http://localhost:8025


## Concept and Traffic
This is a COVID-19 vaccination program app where the traffic is write-heavy during the initial user registration phase and transitions to a more read-heavy load as users frequently check vaccination statuses and appointments.

## Thought Process
To handle fast-paced, high-traffic registration processes efficiently, I've implemented a **Write-back strategy** for database writes. The codebase is concise, and I’ve explained my decision-making process wherever relevant. Here's why it scales efficiently:

- Write-back strategy optimizes fast-paced high-traffic registrations
- Caching is heavily used whenever possible to speed up operations
- Batch updates to reduce database write load
- Efficient read operations powered by caching
- Proper cache invalidation ensures no stale data
- Separation of concerns makes debugging and testing simpler

## Database Structure
- **users**: id, `nid, dob, name, email, vaccine_center_id`
- **vaccine_centers**: `id, name, address, daily_capacity`
- **vaccine_appointments**: `id, date, user_id, vaccine_center_id, status`
- **vaccine_center_daily_usages**: `id, vaccine_center_id, usage_counter`

_All tables include `created_at` and `updated_at` fields._

## Caching with Redis
Redis is used wherever in-memory storage is necessary for quick access, especially for caching services and job queues.

## Jobs
- **BatchScheduleVaccineAppointmentJob**: Distributes schedules on a first-come-first-serve basis, running every hour. Uses the `BookingService` (which taps into both Redis and DB storage) for efficiency.
- **BatchSendAppointmentNotifications**: Notifies users with appointments for the next day. Runs daily at 9 PM.
- **BatchUpdateToVaccinatedStatus**: Updates user statuses to vaccinated after their scheduled appointment. Handles status updates in bulk for efficiency.
- **StoreUserJob**: The only single-user job (could’ve been batched, but I wanted to showcase diversity in handling different cases).

If you want to see the app in action, run `sail up -d` and then `sail php artisan app:init`. This will seed 5000 users. Then, tweak the schedules to shorter intervals (e.g., every 10 seconds) for faster testing. You may connect TablePlus or similar DB Management Tools to see DB Records. Notifications will show up in Mailpit at [localhost:8025](http://localhost:8025).

## Implementing Notification by SMS
Most of the SMS notification setup is already in place. Since we're using **Laravel Notification**, I integrated **Twilio**. The `User` model has the `routeNotificationForTwilio()` method, which utilizes `country_code` and `phone_number` columns—so make sure those fields are in the database. 

For single-country users (like Bangladeshi users), we can hardcode the `country_code`. Then, just add the **Twilio** credentials in the `.env` file and update the `via` method like this:

```php
public function via(object $notifiable): array
{
    return ['mail', 'twilio'];
}
```
That’s it! For more details, check out `app/Notifications/AppointmentNotification.php`.

## Demonstrating Diverse Skills
To showcase the range of my abilities, I utilized **Livewire** for the `/register` route and traditional **Controllers** for the `/search` route. I also worked with **AlpineJS** components for frontend interactions. I wish I could’ve demonstrated more like my skills with **Vue**, **TypeScript**, and **InertiaJS**.

I'm well-versed in **Laravel/PHP**, **Flutter**, **NodeJS**, **VueJS**, **JavaScript**, **TypeScrupt**, **NuxtJS**, and even a bit of **ReactJS**. Always ready to work across stacks and adapt to different project needs.

I've built an open-source Laravel package, the first release was 4 years ago and it's heavily unit tested: https://github.com/farhanshares/laravel-mediaman

## Further Improvements

If I had more time, here's what I would have done to take this project to the next level:

- **Integrate Meilisearch**: For blazing fast search across users and vaccination centers, optimizing search functionality beyond the basic DB queries.
  
- **Advanced Caching Strategies**: Go beyond basic caching by implementing distributed caches with Redis for large-scale deployment and session sharing.

- **Queue Optimization**: Adjust queue worker settings and explore using RabbitMQ for improved job processing efficiency in high-traffic scenarios.
  
-  **PostgreSQL**: I would have used PostgreSQL for its advantages in handling complex queries, better concurrency, and advanced indexing capabilities but opted for the default **Laravel Sail** setup with MySQL to streamline the development process.
  
- **Query Optimization** I plan to look into further query optimizations to enhance performance
  
- **Write Unit Tests**: Implement thorough unit and feature tests, ensuring code quality and robustness, especially for critical processes like user registration and appointment scheduling.

- **Refactor for Scalability**: Further refactoring the codebase for microservices architecture, breaking out key features like notifications and scheduling for better scalability.

- **Frontend Frameworks**: If given more time, I would also demonstrate skills with **Vue**/**TypeScript**/**InertiaJS**, improving the frontend UX with a smoother, reactive UI.

- **Deploy to Cloud**: Automate cloud deployments using **CI/CD pipelines** with services like Forge or Envoyer for real-world use.

With more time, this would have been a more polished and enhanced app.

