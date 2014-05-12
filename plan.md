FUNCTIONALITY
============

Admin
-----

- Add/Modify/Delete Customer
- Add/Modify/Delete Employee
- Generate Invoice
- Record payment
- Employee & Customer functions


Employee
--------

- Add/Modify/Delete Time

Customer
--------

- View invoices
- Add upload/download image files

DATABASE
========

users
-----

- id
- user_login
- user_pass
- user_privileges
    - admin
    - employee
    - customer

customer
--------

- id
- billing_
    - address
    - city
    - state
    - zip
    - phone
    - fax
- mailing_
    - address
    - etc
- notes
- default_rate
- default_invoice_note
- default_invoice_number

customer contact
----------------

- id
- customer_id
- user_id
- name
- phone
- email
- alt_contact1_type
- alt_contact1_value
- alt_contact2_type
- alt_contact2_value
- notes

invoice
-------

- id
- customer_id
- start_date
- end_date
- order_number
- po_number
- invoice_number
- notes

project
-------

- id
- customer_id
- project_number
- description
- rate

time entry
----------

- id
- user_id (employee)
- project_id
- customer_id (from project)
- start_time
- end_time

