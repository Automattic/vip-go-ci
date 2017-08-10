FROM jetbrains/teamcity-agent

# Install PHP-CLI
RUN apt-get update && apt-get install -y \
	php-cli \
	php7.0-xml
