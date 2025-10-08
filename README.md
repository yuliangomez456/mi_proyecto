# Sistema de Mesa de Ayuda - Documentación Completa

# Descripción del Proyecto
Sistema completo de gestión de tickets desarrollado en PHP para la asignación y seguimiento de solicitudes de soporte técnico con roles de Administrador, Técnico y Cliente.

# Tecnologías Utilizadas

### **Backend:**
- **PHP 7.4+** - Lenguaje de programación del servidor
- **MySQL** - Base de datos relacional
- **PDO** - Conexión segura a base de datos
- **Sessions** - Manejo de autenticación

### **Frontend:**
- **HTML5** - Estructura web
- **CSS3** - Estilos y diseño responsive
- **Bootstrap 5.3** - Framework CSS
- **JavaScript** - Interactividad del cliente
- **Font Awesome** - Iconografía

### **Hosting & Deployment:**
- **InfinityFree** - Hosting gratuito
- **FTP/File Manager** - Deployment de archivos
- **PHPMyAdmin** - Gestión de base de datos

## 📁 Estructura del Proyecto

```
Tecnologias/
├── 📁 config/
│   └── conexion.php              # Configuración de BD
├── 📁 modelos/
│   ├── Usuario.php              # Modelo de usuarios
│   └── Ticket.php               # Modelo de tickets
├── 📁 view/
│   ├── login.php                # Autenticación
│   ├── registro.php             # Registro de usuarios
│   ├── admin.php                # Panel administrador
│   ├── cliente.php              # Panel cliente
│   ├── tecnico.php              # Panel técnico
│   └── logout.php               # Cierre de sesión
├── 📁 css/
│   ├── style.css                # Estilos principales
│   └── style2.css               # Estilos secundarios
├── 📁 img/
│   ├── usuario2.png             # Imagen de perfil
│   ├── imagen2.jpg              # Imagen login
│   └── imagen4.jpg              # Imagen registro
└── 📄 index.php                 # Página principal
```

## Base de Datos

### **Estructura de Tablas:**

#### **Tabla: roles**
```sql
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### **Tabla: usuarios**
```sql
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_usuario VARCHAR(100) NOT NULL,
    correo_usuario VARCHAR(100) NOT NULL UNIQUE,
    celular_usuario VARCHAR(20),
    contrasena_usuario VARCHAR(255) NOT NULL,
    rol_id INT NOT NULL DEFAULT 2,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
);
```

#### **Tabla: tickets**
```sql
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    categoria VARCHAR(50) DEFAULT 'General',
    prioridad ENUM('baja', 'media', 'alta') DEFAULT 'media',
    estado ENUM('pendiente', 'en_proceso', 'resuelto', 'cerrado') DEFAULT 'pendiente',
    tecnico_id INT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id)
);
```

### **Datos Iniciales:**

#### **Roles del Sistema:**
```sql
INSERT INTO roles (id, nombre_rol, descripcion) VALUES
(1, 'Administrador', 'Acceso completo al sistema, gestión de usuarios y tickets'),
(2, 'Cliente', 'Puede crear y ver sus propios tickets, eliminar sus tickets'),
(3, 'Tecnico', 'Puede ver tickets asignados, cambiar estados pero no eliminar tickets');
```

#### **Usuario Administrador por Defecto:**
- **Email:** `admin@mesaayuda.com`
- **Contraseña:** `admin123`
- **Rol:** Administrador (ID: 1)

## 👥 Roles y Permisos

### **1. Administrador** (`rol_id = 1`)
- ✅ Gestión completa de usuarios
- ✅ Crear nuevos técnicos
- ✅ Ver todos los tickets
- ✅ Asignar tickets a técnicos
- ✅ Cambiar estados de tickets
- ✅ Estadísticas del sistema

### **2. Cliente** (`rol_id = 2`)
- ✅ Crear nuevos tickets
- ✅ Ver solo sus tickets
- ✅ Asignar técnico al crear ticket (opcional)
- ✅ Eliminar sus propios tickets
- ✅ Ver detalles completos de tickets

### **3. Técnico** (`rol_id = 3`)
- ✅ Ver tickets asignados
- ✅ Cambiar estado de tickets
- ✅ Ver detalles de tickets asignados
- ❌ No puede eliminar tickets
- ❌ No puede ver tickets de otros técnicos

## 🔧 Funcionalidades Implementadas

### **Sistema de Autenticación:**
- Login seguro con verificación de credenciales
- Registro de nuevos usuarios
- Control de sesiones
- Redirección por roles
- Logout seguro

### **Gestión de Tickets:**
- Creación de tickets con categorías y prioridades
- Asignación manual/automática de técnicos
- Seguimiento de estados (pendiente, en proceso, resuelto, cerrado)
- Eliminación de tickets (solo por cliente dueño)
- Filtros y búsqueda

### **Panel de Administración:**
- CRUD completo de usuarios
- Gestión de tickets global
- Asignación de técnicos a tickets sin asignar
- Estadísticas del sistema
- Interfaz responsive con pestañas

### **Características Técnicas:**
- Diseño responsive con Bootstrap
- Validación de formularios en frontend y backend
- Manejo seguro de contraseñas con `password_hash()`
- Protección contra SQL Injection con PDO
- Manejo de errores y excepciones
- Arquitectura MVC básica

## 🚀 Instalación y Configuración

### **Requisitos:**
- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)

### **Pasos de Instalación:**

1. **Clonar/Descargar el proyecto**
2. **Configurar base de datos:**
   ```sql
   CREATE DATABASE mesa_ayuda;
   USE mesa_ayuda;
   -- Ejecutar scripts SQL de estructura
   ```

3. **Configurar conexión** en `config/conexion.php`:
   ```php
   private $host = "localhost";
   private $db_name = "mesa_ayuda";
   private $username = "usuario";
   private $password = "contraseña";
   ```

4. **Subir archivos al servidor** via FTP o File Manager

5. **Acceder al sistema:**
   - URL: `http://tudominio.com/login.php`
   - Usuario: `admin@mesaayuda.com`
   - Contraseña: `admin123`

## 🔒 Seguridad Implementada

- **Hash de contraseñas** con `password_hash()`
- **Prepared statements** para todas las consultas SQL
- **Validación de entrada** en formularios
- **Control de sesiones** por rol
- **Protección contra XSS** con `htmlspecialchars()`
- **Redirecciones seguras** después de login
- **Verificación de permisos** en cada página

## 📊 Características de la Interfaz

### **Diseño Moderno:**
- Paleta de colores profesional
- Cards con efectos hover
- Navbar responsive
- Modales para acciones
- Badges para estados
- Iconografía consistente

### **Experiencia de Usuario:**
- Loading states
- Mensajes de confirmación
- Alertas de éxito/error
- Formularios intuitivos
- Navegación fluida

## 🐛 Solución de Problemas Comunes

### **Error de Conexión a BD:**
```php
// Verificar en config/conexion.php
private $host = "sql300.infinityfree.com"; // Para InfinityFree
private $password = "password_correcto"; // Password de MySQL, no del vPanel
```

### **Error 500 - Internal Server Error:**
- Verificar sintaxis PHP
- Revisar includes de archivos
- Chequear permisos de archivos

### **Login no redirige:**
- Verificar que las tablas estén creadas
- Confirmar que el usuario exista en la BD
- Revisar manejo de sesiones

## 🔄 Flujo de Trabajo Típico

1. **Cliente** crea ticket → Estado: "Pendiente"
2. **Administrador** asigna técnico → Estado: "En Proceso" 
3. **Técnico** trabaja en ticket → Actualiza estado
4. **Técnico** resuelve ticket → Estado: "Resuelto"
5. **Cliente** puede eliminar ticket si es necesario

## 📈 Características Futuras Potenciales

- [ ] Sistema de comentarios en tickets
- [ ] Notificaciones por email
- [ ] Reportes PDF
- [ ] API REST
- [ ] Dashboard con gráficos
- [ ] Sistema de categorías dinámicas
- [ ] Adjuntar archivos a tickets
- [ ] Búsqueda avanzada

## 👨‍💻 Desarrollo Realizado Por

**Proyecto desarrollado** como sistema completo de mesa de ayuda con funcionalidades empresariales reales, implementando mejores prácticas de seguridad y experiencia de usuario.


**¡Sistema listo para producción! 🎉**
