# MangopayBundle
Symgony bundle which adapts Mangopay php API.

#Installation
Just add this line to composer:
```
"melk/mangopay" : "dev-master"
```

#Configuration
Create account on mangopay and add next information to your `config.yml`:
```
#app/config/config.yml
melk_mangopay:
    client_id: your client id
    password: your passphrase
    sandbox: true (default) or false 
```
To turn off sandbox mode just set sandbox parameter in the config file to false
