# Covid 19 Vaccination Program by Farhan Israq

We have the following tables:

* Users:
    id,nid,dob,name,email,vaccine_center_id,vaccine_appointment_id
* VaccineCenters:
    id,name,address,daily_capacity
* VaccineAppointments:
    id,date,user_id,vaccine_center_id,notified_at
* VaccineCenterDailyUsages:
    id,vaccine_center_id,usage_counter

All tables have created_at and updated_at columns.
