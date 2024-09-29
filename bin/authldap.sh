#!/usr/bin/env bash

set -euo pipefail

# Variables
WP_CLI="sudo -E -u www-data -- /app/data/vendor/bin/wp --skip-themes --path=/app/data/public/wp/"
DEFAULT_ROLE="editor"

# Determine if WordPress is installed as a network
if $(${WP_CLI} core is-installed --network); then
    WP_OPTION_GET="${WP_CLI} --format=json --quiet site option get"
    WP_OPTION_UPDATE="${WP_CLI} --format=json --quiet site option update"
else
    WP_OPTION_GET="${WP_CLI} --format=json --quiet option get"
    WP_OPTION_UPDATE="${WP_CLI} --format=json --quiet option update"
fi

# Function to configure LDAP plugin settings
configure_ldap() {
    if [[ -n "${CLOUDRON_LDAP_SERVER:-}" ]]; then
        echo "==> Configuring LDAP"

        local ldap_config_json
        ldap_config_json=$(cat <<EOF
{
    "Enabled"       : true,
    "CachePW"       : false,
    "URI"           : "ldap://${CLOUDRON_LDAP_SERVER}:${CLOUDRON_LDAP_PORT}/${CLOUDRON_LDAP_USERS_BASE_DN}",
    "Filter"        : "(username=%s)",
    "NameAttr"      : "givenName",
    "SecName"       : "sn",
    "UidAttr"       : "username",
    "MailAttr"      : "mail",
    "WebAttr"       : " ",
    "Debug"         : false,
    "DefaultRole"   : "${DEFAULT_ROLE}",
    "GroupEnable"   : false,
    "GroupOverUser" : false,
    "Version"       : 1
}
EOF
        )

        # Update LDAP plugin options
        ${WP_OPTION_UPDATE} authLDAPOptions "${ldap_config_json}"
    fi
}

# Main function to configure LDAP if needed
config_authldap() {
    configure_ldap
}

# Execute the main function
config_authldap