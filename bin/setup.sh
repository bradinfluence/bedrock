#!/usr/bin/env bash

# Define the path to wp-cli
wp="/app/data/vendor/bin/wp --skip-plugins --skip-themes --path=/app/data/public/wp/"

# Function to execute wp-cli commands
function wp_cli_cmd() {
    sudo -E -u www-data -- $wp "$@"
}

domain="{CLOUDRON_APP_DOMAIN}"
wp_cli_cmd core install --url="$domain" --title="Undergoing Maintenance" --admin_user="admin" --admin_password="changeme" --admin_email="admin@bradinfluence.co.uk"

# Generate random password
random_password=$(openssl rand -base64 12)
wp_cli_cmd user create bradinfluence bradley@bradinfluence.co.uk --role=administrator --user_pass="$random_password" --url="$domain"

wp_cli_cmd user delete admin --reassign=bradinfluence --yes

# Example usage of wp_cli_cmd for various configuration tasks
wp_cli_cmd option update rss_use_excerpt 1
wp_cli_cmd option update uploads_use_yearmonth_folders 0
wp_cli_cmd option update permalink_structure '/%category%/%postname%/'
wp_cli_cmd option update blogdescription 'Website coming soon...'
wp_cli_cmd option update timezone_string 'Europe/London'
wp_cli_cmd option update date_format 'l, F j, Y'
wp_cli_cmd comment delete 1 --force
wp_cli_cmd post delete 1 --force
wp_cli_cmd post delete 2 --force

# Disable comments
wp_cli_cmd option update disable_comments_setting 1

# Clean image filenames and mime types
wp_cli_cmd option update clean_image_filenames_mime_types 'all'