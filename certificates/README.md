# Generating a key pair for Simple OAuth

We keep this folder to save our OpenSSL keys.

Open your terminal, place yourself inside the `certificates` folder and run following commands:
```
cd <this_project>/certificates/
openssl genrsa -out private.key 2048
openssl rsa -in private.key -pubout > public.key
chmod 600 private.key
chmod 600 public.key
```

Those keys might be useful if you use a *headless* Drupal such as [ContentaCMS](https://github.com/contentacms/contenta_jsonapi).

Questions? See [this wiki](https://github.com/contentacms/contenta_jsonapi/wiki/Generating-a-key-pair-for-Simple-OAuth)
