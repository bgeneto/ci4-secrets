# Secrets for CodeIgniter 4

This CodeIgniter 4 package offers a secure way to store sensitive information. It encrypts data using your application's default database connection, allowing you to avoid storing secrets such as API keys, certificate passwords, and other sensitive data in plain text within a `.env` file.

## Installation

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

Now you can install this package via Composer:

```sh
composer update
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
