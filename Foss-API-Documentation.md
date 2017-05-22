# FOSS-Cloud API
This restful api makes it easy to access the main features of the FOSS-Cloud in your applications.

------
## Features 

The following features are currently included:

|  REST-Path | Description  |
| --- | --- |
| [/user/login](#user-login) | Verifies the user credentials  |
| [/server/realms](#list-available-realms) | Lists all available realms for the login process  |
| [/vm/list/(types)](#list-virtual-machines) | Lists the user assigned vms by type  |
| [/vm/assign/](#assign-vm) | Assigns the user to a dynamic vm pool |

The following features are only available with the FC4School module:

|  REST-Path | Description  |
| --- | --- |
| [/vm/mapping/list](#list-mapped-vms) | Lists all vms mapped to this MAC address |
| [/vm/mapping/assign](#assign-mapped-vms) | Assigns the user to the mapped vm |


------

## Setup
**In the standard configuration the API is disabled**, so you've to enable it manually. To do so edit the file in `/var/www/localhost/htdocs/vm-manager/protected/config/api_config.php`and set `'enable'`to `"true"`. 

Another optional setting is the `defaultRealm` which is used for every request without explicitly send it. This is for example useful when you authenticate against an Active Directory and are annoyed to append the realm in every request. (For more information see [Login process](#user-login))


## General information
### Basic request
Every request to the API must contain valid **login information**, which are send using the HTTP basic access authentication.  If there're no additional information in the parameter section of the request, you have to send the login information with it! The optional realm, if it's not the default realm, can simply appended to every request.
```
https://username:password@my-example-fosscloud.com/vm-manager/api/.../realm/4000020
```
**Make sure that the [user login errors](#user-login) can occur in every request where a login is required**

### Server response
Every response from the server is encoded in JSON and contains the following information:
```
{
    "status": 401,
    "code": 401002,
    ...
}
```
Every error contains also an developer description so you know what's exactly wrong:
```
{
    ...
    "devDescription": "HTTP-Auth missing"
    ...
}
```

If the request should return something there's also another key, which bases on the request.
```
{
   "vms":[
      {
         ...
      }
   ]
}
```

### Error handling
The error handling bases on the default HTTP status codes. The following ones can occur:

| Code | Name | Description  |
|---|---|---|
| 200 | SUCCESS | The request was successful |
| 400 | BAD REQUEST| The client sends something wrong |
| 401 | UNAUTHORIZED | The request wasn't authroized |
| 403 | FORBIDDEN | Not enough permission to access this action |
| 404 | NOT FOUND| The requested action doesn't exist |
| 500 | INTERNAL SERVER ERROR| There was an server problem handling the request |

In addition to this status code every response contains an error/success code with a description giving the developer more information about the occured error. These are listed in the request description below and structured like this: `4001002`. The `401` shows that this is an `UNAUTHROIZED` error and the `002` is just an incrementing number. For readability the codes contains spaces in the tables below, which doesn't exist in the response. These numbers are grouped into ranges, for example the user login request uses codes between `1-10`. You'll find the ranges in the request descriptions.

#### General codes
This codes can occur in every request

| Code | Description|
|---|---|
|404 000 | Unkown action |
|500 000 | Unkown internal server error, have a look on the developer description of the response |
|500 022 | The api is deactivated |
|500 023 | The default realm is missing in the config |

## Requests
###  User login
#### Path
```
/user/login
```
#### Parameters
Just the basic request which already contains the required login information (see: [Basic request](#basic-request))

#### Data response
There's no data response. The error/success codes gives you the information whether the login was successfull.

#### Codes
**Range:** 1-10

| Code | Description|
|---|---|
|200 001 | The credentials are correct and the login was successfull |
|400 002 | The HTTP basic authentication isn't provided |
|401 003 | The credentials are incorrect (general) |
|401 004 | The FOSS-Cloud doesn't contains this realm |
|400 005 | The username or the password is empty |


---

###  List available realms
#### Path
```
/server/realms
```

#### Parameters
For this request is no authentification needed, but if you do so, it will also work.

#### Codes
**Range:** 11-20

| Code | Description|
|---|---|
|200 011 | The realms were successfull received |


---

###  List virtual machines
#### Path
```
/vm/list/(%type%)
```
#### Parameters
**%type%**

| Option | Description|
|---|---|
| leave it or owner (default) | Lists all vms available for this user |
| dynamic | Lists all dynamic vms available for this user |
| persistent | Lists all persistent vms available for this user |
| all| Lists all vms that are running on the foss cloud (requires permission *persistentVM.access - sstUserRightValue=all*) |

#### Data response
A result containing a persistent vm looks like this:
```
{
   ...
   "vms":[
      {
         "uuid":"5750786d-045c-4d3c-85c3-4c169aabcc7b",
         "name":"Test_Machine",
         "ip":"node-01.my-example-fosscloud.com",
         "node":"node-01.my-example-fosscloud.com",
         "port":5901,
         "password":"PFiFibef19pa",
         "status":"online",
         "type":"persistent",
         "subtype":"Desktop",
         "os":"windows",
         "pool":"57501ac5-3a14-41cb-b225-8e787c41505a"
      },
      ...
   ]
}
```
The status of the VM can either be `online` or `offline`

In the FOSS-Cloud there're virtual machines that are assigned or available for a user. For example if you've a dynamic vm pool to which the user belongs, the vm isn't automatically assigned to this user. The webinterface do this assigning process automatically when you click on the view button. But here you've to make two requests. One for the listing process that gives you all vms that are available for this user and one for the assigning process (see [Assign vm](#assign-vm)). A result for a dynamic vm pool where the user isn't assigned yet looks like this:
```
{
   ...
   "vms":[
      {
         "uuid":null,
         "name":"DynVm-Test",
         "ip":null,
         "node":null,
         "port":null,
         "password":null,
         "status":"assignable",
         "type":"dynamic",
         "subtype":null,
         "os":null,
         "pool":"57e3bd10-1934-4b4f-3d4c-6e31473bddab"
      },
      ...
   ]
}
```
You see that the status is `assignable`, which says that the user can be assigned to this vm pool.
If the user is already assigned to a vm pool the response looks like the one from the persistent machine, but the `status` is set to `assigned` instead of online or offline.
```
{
   ...
   "vms":[
      {
         "uuid":"7a476dcc-7e27-495b-9dd5-f599b8ecff16",
         "name":"DynVm-Test",
         "ip":"node-01.my-example-fosscloud.com",
         "node":"node-01.my-example-fosscloud.com",
         "port":5943,
         "password":"7a476dcc-7e27-495b-9dd5-f599b8ecff16",
         "status":"assigned",
         "type":"dynamic",
         "subtype":"Desktop",
         "os":"windows",
         "pool":"57e3bd10-1934-4b4f-3d4c-6e31473bddab"
      },
      ...
   ]
}
```

#### Codes
**Range:** 31-40

| Code | Description|
|---|---|
|200 031 | The vm list was successfully received |
|400 032 | The used type for the %type%-parameter is unkown |
|403 033 | Not enough permission to list "all" vms |


---


### Assign vm
#### Path
```
/vm/assign/%pool%
```

#### Parameters
**%pool%**

| Option | Description|
|---|---|
| uuid | The pool uuid the user should be assigned to |

#### Data response
Returns the vm details, like the `/vm/list` response, to which the user was assigned

#### Codes
**Range:** 41-50

| Code | Description|
|---|---|
|200 041 | The vm was successfully assigned |
|400 042 | There's no pool with this uuid |
|500 043 | There're currently no vms in this pool available  |


---

## FOSS-Cloud for schools only
The following api methods are only available if you've bought and installed FC4School.
**You've to use the dynvmuser for this requests** 

### List mapped vms
This method lists all vms which are mapped to a specific mac-address. It uses either the MAC address of the remote host which only works locally or you can pass a custom MAC address by parameter

#### Path
```
/vm/mapping/list/(%MAC%)
```

#### Parameters
**%mac%**

| Option | Description|
|---|---|
| leave it | Tries to determine mac address automatically using the remote ip (only works locally) |
| MAC address | Use this MAC address to lookup the mapped vm |

**This paramater is only available if you set `macByParameter` in `api_config.php` to true.**
This is because an attackter could scan the network for possible MAC addresses that are mapped to virtual machines and simply append it to this request and have fully access to this VM. So only enable this parameter when you know what you're doing! :)


#### Data response
Output is the same as the `/vm/list`method. 
**Make sure that you also have to assign a dynamic mapped vm using the [Assign mapped vm](#assign-mapped-vm) request!**

#### Codes
**Range:** 51-60

| Code | Description|
|---|---|
|200 051 | The vm was successfully received |
|500 052 | The FC4School module isn't installed |
|400 053 | You've to authentificate with the dynvmuser |
|400 054 | No vm for this mac address available |
|400 055 | Unkown mac address |
|403 056 | The `macByParameter` option is disabled |


---

### Assign mapped vms
Assigns the mapped vm to this MAC address.

#### Path
```
/vm/mapping/assign/%pool%/(%MAC%)
```
#### Parameters

%pool%
| Option | Description|
|---|---|
| UUID | The pool where the MAC address should be assigned to |

**%mac%**

| Option | Description|
|---|---|
| leave it | Tries to determine mac address automatically using the remote ip (only works locally) |
| MAC address | Use this MAC address to lookup the mapped vm |

**This paramater is only available if you set `macByParameter` in `api_config.php` to true.**
This is because an attackter could scan the network for possible MAC addresses that are mapped to virtual machines and simply append it to this request and have fully access to this VM. So only enable this parameter when you know what you're doing! :)

#### Data response
Output is the same as the `/vm/assign`method. 

#### Codes
**Range:** 61-70

| Code | Description|
|---|---|
|200 061 | The vm was successfully assigned |
|500 062 | The FC4School module isn't installed |
|400 063 | You've to authentificate with the dynvmuser |
|400 064 | No VM for this MAC address available |
|400 065 | The pool isn't valid |
|400 067 | The user is already assigned to this vm |
|400 068 | The `macByParameter` option is disabled |
