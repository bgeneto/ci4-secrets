# CI4 Secrets

CI4 Secrets is a CodeIgniter 4 package designed to provide a secure and reliable way to store sensitive data, such as API keys, certificate passwords, and other confidential information. By utilizing CI4's encryption key, CI4 Secrets encrypts data at rest and stores it securely using your default database connection. 

With CI4 Secrets, you can eliminate the risk of storing sensitive information in plain text within your `.env` file, reducing the exposure of your application to potential security breaches. Instead, store your secrets securely and access them easily through the package's intuitive interface.

## Installation

### 1. Edit your composer json file 

Just setup the repository like this in your `composer.json` file:

```json
    "require": {
        "php": "^8.1",
        "codeigniter4/framework": "^4.0",
        "bgeneto/secrets": "dev-main"
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories": {
        "secrets": {
            "type": "vcs",
            "url": "https://github.com/bgeneto/ci4-secrets.git"
        }
    },
```

### 2. Install this package via composer:

```sh
composer update
```

### 3. Create a CI4 encription key (only if your app does not have one yet)

```sh
php spark key:generate
```

This will put a encryption key in your `.env` similar to this:

```
encryption.key = hex2bin:2869d5b78952d4268d1cf5fb37d24e6850875fd86f246f959a7c315718d039a2
```

### 4. Publish the package config file 

```sh
php spark secrets:publish
```

### 5. Create required database tables

This packages uses two tables: `secrets` and `secrets_log`. In order to create those tables you have to issue the command (ensure you have a properly configured database connection):

```sh
php spark migrate -all
```

## Usage

### Secrets Library

The `Secrets` library provides methods to securely store, encrypt, and decrypt sensitive data using CodeIgniter 4's encryption service.

#### Methods

- `encrypt(string $data): string`
- `decrypt(string $encryptedData): string`
- `store(string $key, string $value, bool $log = true): bool`
- `update(string $key, string $value, bool $log = true): bool`
- `retrieve(string $key, bool $log = true): ?string`
- `delete(string $key, bool $log = true): bool`

### Secrets Command

The `secrets` command allows you to manage encrypted secrets in the database.

#### Available Operations

- `add`: Add a new secret.
- `update`: Update an existing secret.
- `delete`: Delete a secret.
- `list`: List all secret keys.

#### Usage Examples

```sh
# Add a new secret
php spark secrets add --key=api_key --value=sk_12345678

# Add via interactive mode
php spark secrets add

# Force add if key exists
php spark secrets add --key=api_key --value=sk_12345678 --force

# Update an existing secret
php spark secrets update --key=api_key --value=sk-87654321

# Update interactively
php spark secrets update

# Delete a secret with inline parameter
php spark secrets delete --key=api_key

# Or delete in interactive mode
php spark secrets delete

# List all secrets
php spark secrets list

## Get help
php spark secrets
```

## License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
