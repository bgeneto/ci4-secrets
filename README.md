# CI4 Secrets

CI4 Secrets is a CodeIgniter 4 package designed to provide a secure and reliable way to store sensitive data, such as API keys, certificate passwords, and other confidential information. By utilizing CI4's encryption key, CI4 Secrets encrypts data at rest and stores it securely using your default database connection. 

With CI4 Secrets, you can eliminate the risk of storing sensitive information in plain text within your `.env` file, reducing the exposure of your application to potential security breaches. Instead, store your secrets securely and access them easily through the package's intuitive interface.

## 1. Installation
---

### 1.1 Composer + Packagist

```bash
composer require bgeneto/ci4-secrets
```

### 1.2 Composer + GitHub:

Just setup the repository like this in your project's `composer.json` file:

```json
{
    "require": {
        "your-project/other-dependencies": "...",
        "bgeneto/ci4-secrets": "dev-main"
    },

    "repositories": {
        "sanitize": {
            "type": "vcs",
            "url": "https://github.com/bgeneto/ci4-secrets.git"
        }
    }
}
```

### 1.3 Composer + Local:

```bash
git clone https://github.com/bgeneto/ci4-secrets.git /path/to/your/local/ci4-secrets	
```

Now edit your `composer.json` file and add a new repository:

```json
{
    "require": {
        "your-project/other-dependencies": "...",
        "bgeneto/ci4-secrets": "dev-main"
    },
    
    "repositories": {
        "sanitize": {
            "type": "path",
            "url": "/path/to/your/local/ci4-secrets"
        }
    }
}
```

### 2. Check if you have an encryption key

Check in your `.env` or in `Config\Encryption` if you have an encryption key already configured, if not just run this spark command below: 

```sh
php spark key:generate
```

This will put a encryption key in your `.env` similar to this:

```
encryption.key = hex2bin:2869d5b48952d4268d1cf5fb37d24e6850875fd86f246f959a7c315718d039a2
```

### 3. Publish the package config file 

```sh
php spark secrets:publish
```
This will create a new `Config\Secrets.php` file that you can customize.

### 5. Create the required table

This packages uses only one database table called `secrets` . You have to run the spark migration command to create it. 

```bash
php spark migrate --all
```



## 2. Usage
---

### 2.1 CLI Usage

The Secrets package provides the following spark new command: `php spark secrets` with the available options:

#### Available Operations

- `add`: Add a new secret.
- `update`: Update an existing secret.
- `delete`: Delete a secret.
- `list`: List all secret keys.
- `get`: Get a secret value.

#### Usage Examples

```sh
# Add a new secret via interactive mode:
php spark secrets add

# Add a new secret directly using its name (key) and value:
php spark secrets add --key=api_key --value=sk_12345678

# Force add if key exists
php spark secrets add --key=api_key --value=sk_12345678 --force=yes

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

# Get a secret value
php spark secrets get

## Get help
php spark secrets
```

The `Secrets` library provides methods to securely store, encrypt, and decrypt sensitive data using CodeIgniter 4's encryption service anywhere (model, controllers...).

#### Methods

- `encrypt(string $data): string`
- `decrypt(string $encryptedData): string`
- `store(string $key, string $value, bool $log = true): bool`
- `update(string $key, string $value, bool $log = true): bool`
- `retrieve(string $key, bool $log = true): ?string`
- `delete(string $key, bool $log = true): bool`

## License

This package is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
