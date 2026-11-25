#!/bin/sh
set -eu

cd "$(dirname "$0")"
umask 077
mkdir -p certs
chmod 755 certs

if [ ! -s certs/dhparam.pem ]; then
    openssl dhparam -out certs/dhparam.pem 2048
fi

if [ -s certs/ldap.key ] \
    && openssl x509 -in certs/ca.crt -checkend 86400 -noout >/dev/null 2>&1 \
    && openssl x509 -in certs/ldap.crt -checkend 86400 -noout >/dev/null 2>&1 \
    && openssl verify -CAfile certs/ca.crt -verify_hostname ldap certs/ldap.crt >/dev/null 2>&1; then
    exit 0
fi

temporary=$(mktemp -d ./certs/.generate-XXXXXX)
trap 'rm -f "$temporary/ca.key" "$temporary/ca.crt" "$temporary/ca.srl" "$temporary/ldap.key" "$temporary/ldap.csr" "$temporary/ldap.crt"; rmdir "$temporary"' EXIT

openssl req -x509 -newkey rsa:2048 -nodes -sha256 -days 365 \
    -subj '/CN=Prevarisc local LDAP test CA' \
    -addext 'basicConstraints=critical,CA:TRUE' \
    -addext 'keyUsage=critical,keyCertSign,cRLSign' \
    -keyout "$temporary/ca.key" -out "$temporary/ca.crt"

openssl req -new -newkey rsa:2048 -nodes -sha256 \
    -subj '/CN=ldap' -addext 'subjectAltName=DNS:ldap' \
    -keyout "$temporary/ldap.key" -out "$temporary/ldap.csr"

openssl x509 -req -in "$temporary/ldap.csr" \
    -CA "$temporary/ca.crt" -CAkey "$temporary/ca.key" -CAcreateserial \
    -days 365 -sha256 -extfile tls.cnf -out "$temporary/ldap.crt"

openssl verify -CAfile "$temporary/ca.crt" -verify_hostname ldap "$temporary/ldap.crt"
chmod 644 "$temporary/ca.crt" "$temporary/ldap.crt"
mv "$temporary/ca.crt" "$temporary/ldap.crt" "$temporary/ldap.key" certs/
