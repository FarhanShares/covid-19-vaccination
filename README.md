# Covid 19 Vaccination Program by Farhan Israq

## Concept and Traffic
It's a COVID-19 vaccination program app, the traffic pattern would typically be write-heavy at the beginning (due to user registrations) and then gradually shift towards being read-heavy (for users checking their vaccination status or appointment details).

## Thought Process
Creating a very fast-paced high-traffic 'registration' process for apps like Covid-19 Vaccination Program can be streamlined with efficient database writes. For that, I've chosen "Write-back strategy". The codebase is documented sufficiently and briefly whenever needed. I've tried explaining why I chose this over that sort of decisions in almost every places. Kindly read through the codebase. The key features that makes it efficient and scalable is because:
* Write-back strategy for the fast paced high traffic registration process
* Heavily utilized caching strategy whenever possible
* Batch updates to minimize database writes
* Efficient database reads with caching
* Proper invalidation of cache
* Separation of concern for easier debugging and testing


## Database table structure
* users:
    id,nid,dob,name,email,vaccine_center_id
* vaccine_centers:
    id,name,address,daily_capacity
* vaccine_appointments:
    id,date,user_id,vaccine_center_id,status
* vaccine_center_daily_usages:
    id,vaccine_center_id,usage_counter

All tables have created_at and updated_at columns.

## Caching and Redis
There are a few services and jobs, which requires an efficient in-memory data storage. I.e. Redis can be a good candidate here. I've utilized redis whenever possible.
