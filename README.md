# ScandiPWA_CustomerGraphQl

**CustomerGraphQl** provides type and resolver information for the GraphQl module
to generate customer information endpoints. 

This module extends the M2 GraphQl customer queries and mutations. 
New endpoints will be documented in future releases.

## Custom query documentation

### Mutation `forgotPassword`

```graphql
mutation ForgotPassword($email: String!) {
  	forgotPassword(email: $email) {
  		status
	}
}
```

The variables for input above might look like:

```json
{
    "email": "alfreds+valid12@gmail.com"
}
```

### Mutation `resetPassword`

```graphql
mutation ResetPassword(
  $token: String!,
  $password: String!,
  $password_confirmation: String!
) {
  resetPassword(
    token: $token,
  	password: $password,
  	password_confirmation: $password_confirmation
  ) {
    status
  }
}
```

```json
{
  "token": "7nk0Ch7D5SZsPHWKycAyGdonAM9MnHRw",
  "password": "MyNewPassword123_",
	"password_confirmation": "MyNewPassword123_"
}
```

### Mutation `confirmCustomerEmail`

Here is an example use of it:

```graphql
mutation ConfirmCustomerEmail(
    $password: String!
    $key: String!
    $email: String!
) {
    confirmCustomerEmail(
        password: $password
        key: $key
        email: $email
    ) {
        status
        token
        customer {
            id
            firstname
            lastname
            email
            is_subscribed
            addresses {
                city
                country_id
                street
                telephone
                firstname
                lastname
                postcode
                street
                default_shipping
                default_billing
            }
        }
    }
}
```

The variables for input above might look like:

```json
{
    "key": "0129309912",
    "email": "alfreds+valid12@gmail.com",
    "password": "Testing123_"
}
```

### Mutation `resendConfirmationEmail`

> ⚠️ This is not tested but implemented !!! ⚠️

Here is an example use of it:

```graphql
mutation ResendConfirmationEmail(
    $email: String!
) {
    resendConfirmationEmail(
        email: $email
    ) {
        status
    }
}
```

The variables for input above might look like:

```json
{
    "email": "alfreds+valid12@gmail.com"
}
```
