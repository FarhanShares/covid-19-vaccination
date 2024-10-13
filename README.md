# Covid 19 Vaccination Program by Farhan Israq

We have the following tables:

* users:
    id,nid,dob,name,email,vaccine_center_id
* vaccine_centers:
    id,name,address,daily_capacity
* vaccine_appointments:
    id,date,user_id,vaccine_center_id,status
* vaccine_center_daily_usages:
    id,vaccine_center_id,usage_counter

All tables have created_at and updated_at columns.

TODO:
* appointment
* notification
* improvements
