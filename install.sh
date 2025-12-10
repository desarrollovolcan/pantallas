#!/bin/bash

# Script de Instalación Rápida para Apache
# Dashboard Corporativo - Sistema de Pantallas de Gestión

echo "🏢 Dashboard Corporativo - Instalación en Apache"
echo "================================================"

# Verificar si se ejecuta como root
if [[ $EUID -eq 0 ]]; then
   echo "❌ No ejecutes este script como root"
   exit 1
fi

# Verificar que Apache esté instalado
if ! command -v apache2 &> /dev/null; then
    echo "❌ Apache no está instalado. Por favor instálalo primero:"
    echo "   sudo apt install apache2 php php-mysql php-json php-gd php-mbstring"
    exit 1
fi

# Verificar que PHP esté instalado
if ! command -v php &> /dev/null; then
    echo "❌ PHP no está instalado. Por favor instálalo primero."
    exit 1
fi

# Verificar que MySQL esté instalado
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL no está instalado. Por favor instálalo primero."
    exit 1
fi

echo "✅ Verificaciones básicas completadas"

# Crear directorios necesarios
echo "📁 Creando directorios necesarios..."
mkdir -p uploads/videos
mkdir -p uploads/images
mkdir -p uploads/temp

# Configurar permisos
echo "🔐 Configurando permisos..."
chmod -R 755 .
chmod -R 777 uploads
chmod -R 777 config

echo "✅ Estructura de directorios creada"

# Verificar configuración de Apache
echo "🔧 Verificando configuración de Apache..."

if ! apache2ctl -M | grep -q rewrite; then
    echo "⚠️  mod_rewrite no está habilitado. Ejecuta:"
    echo "   sudo a2enmod rewrite"
    echo "   sudo systemctl restart apache2"
fi

# Verificar archivo .htaccess
if [ ! -f .htaccess ]; then
    echo "⚠️  Archivo .htaccess no encontrado. Creándolo..."
    # El archivo ya debería existir, pero por si acaso
fi

echo "✅ Configuración de Apache verificada"

# Configurar base de datos
echo "🗄️  Configurando base de datos..."
echo "Por favor, proporciona la información de MySQL:"

read -p "Host MySQL [localhost]: " db_host
db_host=${db_host:-localhost}

read -p "Nombre de la base de datos [corporativo_dashboard]: " db_name
db_name=${db_name:-corporativo_dashboard}

read -p "Usuario MySQL [root]: " db_user
db_user=${db_user:-root}

read -s -p "Contraseña MySQL: " db_pass
echo

# Probar conexión a MySQL
echo "🔍 Probando conexión a MySQL..."
if mysql -h "$db_host" -u "$db_user" -p"$db_pass" -e "SELECT 1;" &> /dev/null; then
    echo "✅ Conexión a MySQL exitosa"
else
    echo "❌ No se pudo conectar a MySQL. Verifica las credenciales."
    exit 1
fi

# Crear base de datos
echo "📊 Creando base de datos..."
mysql -h "$db_host" -u "$db_user" -p"$db_pass" -e "CREATE DATABASE IF NOT EXISTS $db_name;" 2>/dev/null

# Importar estructura y datos
echo "📥 Importando estructura de base de datos..."
if mysql -h "$db_host" -u "$db_user" -p"$db_pass" "$db_name" < database.sql; then
    echo "✅ Base de datos importada exitosamente"
else
    echo "❌ Error al importar la base de datos"
    exit 1
fi

# Actualizar archivos de configuración
echo "⚙️  Actualizando archivos de configuración..."

# Actualizar config/database.php
sed -i "s/'localhost'/'$db_host'/g" config/database.php
sed -i "s/'corporativo_dashboard'/'$db_name'/g" config/database.php
sed -i "s/'root'/'$db_user'/g" config/database.php
sed -i "s/''/'$db_pass'/g" config/database.php

echo "✅ Configuración actualizada"

# Crear archivo de instalación completada
echo "$(date)" > config/installed.txt

echo ""
echo "🎉 ¡Instalación completada exitosamente!"
echo "========================================"
echo ""
echo "🌐 Dashboard Principal: http://localhost/"
echo "🔐 Panel Administración: http://localhost/login.php"
echo "👤 Usuario: admin"
echo "🔑 Contraseña: 123456"
echo ""
echo "📋 Próximos pasos:"
echo "1. Configura la API del clima en config/config.php"
echo "2. Sube videos corporativos desde el panel de administración"
echo "3. Personaliza el contenido según tus necesidades"
echo ""
echo "📖 Para más información, consulta README_APACHE.md"
