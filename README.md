## domain = localhost:8000/api

## domain/register
### body:
* name => (3<=length<=16)
* email
* password => (8<=length<=16)
* password_confirmation

## domain/login
### body
* email 
* password

## domain/logout (must have token)
### body (empty)

## domain/users (must have token)
### body (empty)