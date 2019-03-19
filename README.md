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
  $firstname: String!, 
  $password: String!, 
  $lastname: String!, 
  $email: String!,
  $addresses: [CustomerAddressInput!]
) {
  createCustomer(
    customer: {
    	firstname: $firstname,
      lastname: $lastname,
      email: $email,
      addresses: $addresses
    }, 
    password: $password
  ) {
    id
  }
}
```

The variables for input above might look like:

```json
{
  "firstname": "Alfred",
  "lastname": "Genkin",
  "email": "alfreds@gmail.com",
  "addresses": [
    {
    	"firstname": "Alfreds",
    	"lastname": "Genkins"
  	}
  ],
  "password": "option123"
}
```