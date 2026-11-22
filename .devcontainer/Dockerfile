# Ambiente de demonstração e desenvolvimento (GitHub Codespaces / Dev Containers).
FROM mcr.microsoft.com/devcontainers/base:ubuntu-24.04

RUN apt-get update \
    && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        php8.3-cli php8.3-mysql php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml php8.3-intl php8.3-zip \
        unzip composer mariadb-server \
    && rm -rf /var/lib/apt/lists/*
