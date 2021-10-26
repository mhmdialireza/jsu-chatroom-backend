# **Chatroom API**
  _this is a simple api for internship last test. <br>
  domain for all http verbs: localhost:8000/api_
  -
<hr>

## **Authentication**

### **Register**

  * **URL**

    _/register_

  * **Method:**

    `POST`
  
  * **URL Params**

    None 

    **Required:**
 
    `id=[integer]`

    **Optional:**
 
    `photo_id=[alphanumeric]`

  * **Dody Params**

    _name_ <br>
    _email_ <br>
    _password_ <br>
    _password_confirmation_ <br>

### **Login**
  * **URL**
  
    _/login_

  * **Method:**

    `POST`
  
  * **URL Params**

    None

  * **Dody Params**

    _email_ <br>

### **Logout**

<_you can use this for logout (it needs auth token)._>

* **URL**

  _/logout_

* **Method:**

  `POST`

* **URL Params**

  None

* **Dody Params**

  _password_ <br>

---

## **Users**

### **Get all users**

<_you can get all the chatroom users (it needs auth token and you should be admin)._>

* **URL**

    _/users_

* **Method:**

  `GET`

* **URL Params**

  None

* **Dody Params**

  None

### **Delete one user**

<_you can get all the chatroom users (it needs auth token)._>

* **URL**

  _/users/{user_id}_

* **Method:**

  `GET`

* **URL Params**

  None

* **Dody Params**

  None


### **Delete a user**

<_you can delete a user form chatroom (it needs auth token and you should be admin)._>

* **URL**

  _/users/delete/{user_id}_

* **Method:**

  `DELETE`

* **URL Params**

  None

* **Dody Params**

  None

___

## **Rooms**

### **Get all rooms**

<_you can Get all of Rooms that exists in chatroom and login user didn't join (it needs auth token)._>

* **URL**

  _/rooms_

* **Method:**

  `GET`

* **URL Params**

  None

* **Dody Params**

  None

### **Get all user's rooms**

<_you can Get all of Rooms that a user join them (it needs auth token)._>

* **URL**

  _/rooms/user_

* **Method:**

  `GET`

* **URL Params**

  None

* **Dody Params**

  None

### **Get a room**

<_you can Get special Rooms information (it needs auth token)._>

* **URL**

  _/rooms/{room_name}_

* **Method:**

  `GET`

* **URL Params**

  None

* **Dody Params**

  None

### **Create room**

<_you can create a Room (it needs auth token)._>

  * **URL**

    _/rooms_

  * **Method:**

    `POST`
  
  * **URL Params**

    None

  * **Dody Params**

    _name_ <br>
    _access (private or public)_ <br>
    _description_ <br>

### **Join room**
<_(it needs auth token)._>
* **URL**

  _/rooms/join_

* **Method:**

  `PUT`

* **URL Params**

  None

* **Dody Params**

  _name (name of the room you like to join)_ <br>

### **Left room**

<_(it needs auth token)._>

* **URL**

  _/rooms/left_

  * **Method:**

  `PUT`

* **URL Params**

  None

* **Dody Params**

  _name (name of the room you like to left)_ <br>

### **Reset key**
<_(it needs auth token)._>

* **URL**

  _/rooms/reset-key_

* **Method:**

  `PUT`

* **URL Params**

  None

* **Dody Params**

  _name (name of the room you like to reset its key)_ <br>
  _key_ <br>
  _newKey_ <br>
  _newKey_conformation_ <br>

  **if you want to use _auto generate_, you should this way:**

  _name_ <br>
  _key_ <br>
  _auto_generate (boolean)_ <br>

### **Edit room**
<_(it needs auth token)._>

* **URL**

  _/rooms/{room_id}_

* **Method:**

  `PUT`

* **Dody Params**

  _name_ <br>
  _description_ <br>
  _access_ <br>

  **if access is private you should send key**
  _key_ <br>

### **Edit room**
<_(it needs auth token)._>

* **URL**

  _/rooms/{room_id}_

* **Method:**

  `PUT`

* **Dody Params**

  _name_ <br>
  _description_ <br>
  _access_ <br>
  
  **if access is private you should send key**
  _key_ <br>



## **Message** 
___
### **Get messages of one room**
<_(it needs auth token)._>

  * **URL**

    _/messages/room/{room_id}_

  * **Method:**

    `Get`
  
  * **URL Params**

    None

  * **Dody Params**
   
    none

### **Send message**
<_(it needs auth token)._>

  * **URL**

    _/messages/room_

  * **Method:**

    `Get`
  
  * **URL Params**

    None

  * **Dody Params**
   
    _message_