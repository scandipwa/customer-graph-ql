# ScandiPWA_CustomerGraphQl

**CustomerGraphQl** provides type and resolver information for the GraphQl module
to generate customer information endpoints. 

> ⚠️ This is work in progress, use on your own caution ⚠️

This module implements the M2 dev customer endpoints and provides new.
New endpoints will be documented in future releases.

Modules is divided in two parts:
- `overwrite` – simply overwrites M2 core files with M2 dev customer account features
- `src` – the ScandiPWA module with updated GraphQL schema and new Resolver for new fields.

## Custom endpoint documentation

### Mutation `createCustomer`

Here is an example use of it:

 ```graphql
mutation CreateCustomer(
    $firstname: String!
    $password: String!
    $lastname: String!
    $email: String!
    $addresses: [CreateCustomerAddressInput!]
) {
    createCustomer(
        customer: {
            firstname: $firstname
            lastname: $lastname
            email: $email
            addresses: $addresses
        }
        password: $password
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
    "firstname": "Alfred",
    "lastname": "Genkin",
    "email": "alfreds+valid12@gmail.com",
    "password": "Testing123_",
    "addresses": [
        {
            "firstname": "Alfreds",
            "lastname": "Genkins",
            "telephone": "22522240",
            "country_id": "LV",
            "city": "Riga",      
            "street": [ "Bitera 15" ],
            "postcode": "LV-1067"
        }
    ]
}
```

### Mutation `confirmCustomerEmail`

> ⚠️ This is not tested but implemented !!! ⚠️

Here is an example use of it:

```graphql
mutation ConfirmCustomerEmail(
    $password: String!
    $key: String!
    $id: String!
) {
    confirmCustomerEmail(
        password: $password
        key: $key
        id: $id
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
    "id": "0000000000",
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