Place PEM key files here

  facility_priv_key.pem  - your gateway private key (for signing responses)
  facility_pub_key.pem   - your gateway public key (give to the bank)
  fuib_pub_key.pem       - bank (PS) public key (for verifying requests)

Generate RSA keypair example:

  openssl genrsa -out facility_priv_key.pem 2048
  openssl rsa -in facility_priv_key.pem -pubout -out facility_pub_key.pem

Paths are configured in config/pumb.ini (FACILITY_PRIVATE_KEY, etc.).
