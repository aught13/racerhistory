# Templates Directory Documentation

## Overview
This directory contains all the view templates for the RacerHistory CakePHP application. Templates are organized following CakePHP 5.x conventions and use Bootstrap 5.3.2 for styling.

## Directory Structure

```
templates/
├── Admin/                   # Admin interface templates
│   ├── Dashboard/          # Admin dashboard views
│   ├── Users/              # Admin user management views
│   └── ...                 # Additional admin modules (Sports, Teams, Games, etc.)
├── element/                # Reusable template elements
│   ├── Admin/              # Admin-specific elements
│   └── flash/              # Flash message templates
├── email/                  # Email templates
│   ├── html/               # HTML email templates
│   └── text/               # Plain text email templates
├── Error/                  # Error page templates
├── layout/                 # Layout templates
│   ├── admin.php           # Admin layout
│   ├── ajax.php            # AJAX layout
│   ├── default.php         # Main application layout
│   ├── error.php           # Error layout
│   ├── install.php         # Installer layout
│   └── email/              # Email layouts
├── Pages/                  # Static page templates
├── Blog/                   # Public blog templates
├── Users/                  # User authentication templates
├── Games/, People/, Seasons/, Stats/  # Public feature templates
└── plugin/                 # Plugin template overrides
```

## Layout Templates

### default.php
- **Purpose**: Main application layout with Bootstrap 5.3.2 framework
- **Features**:
  - Responsive navigation with authentication status
  - CDN-loaded Bootstrap CSS/JS with integrity verification
  - jQuery 3.7.1 for enhanced functionality
  - Flash message display area
  - Footer with copyright information
- **Variables**: `$this` (AppView instance)

### admin.php
- **Purpose**: Administrative interface layout
- **Features**:
  - Bootstrap 5.3.2 framework
  - Admin navigation element integration
  - Simplified styling for admin tasks
- **Variables**: `$this` (AppView instance)

### ajax.php
- **Purpose**: Minimal layout for AJAX responses
- **Features**: Content-only output without full page structure

### error.php
- **Purpose**: Error page layout
- **Features**: Clean error presentation with minimal styling

## User Templates

### Users/login.php
- **Purpose**: User authentication form
- **Features**:
  - Bootstrap card-based design
  - Password visibility toggle with Bootstrap Icons
  - Form validation support
  - Responsive design (mobile-friendly)
  - Database connection info display (development)
- **Variables**: `$dbInfo` (optional database information)

### Users/register.php
- **Purpose**: User registration form
- **Features**:
  - User entity form binding
  - Password visibility toggle
  - Bootstrap form styling
  - Client-side validation ready
- **Variables**: `$user` (User entity for form binding)

### Users/logout.php
- **Purpose**: Logout confirmation page
- **Features**: Simple logout confirmation message

### Users/reset_password.php
- **Purpose**: Password reset form
- **Features**: Password reset functionality with form validation

## Admin Templates

### Admin/Dashboard/index.php
- **Purpose**: Main admin dashboard landing page
- **Features**:
  - Welcome message for authenticated administrators
  - Basic dashboard layout structure
- **Variables**: Authentication status from identity

### Admin/Users/
- **Purpose**: User management interface
- **Templates**:
  - `index.php`: User listing with pagination
  - `add.php`: Add new user form
  - `edit.php`: Edit existing user form
  - `manage.php`: User management dashboard
  - `login.php`: Admin-specific login

## Element Templates

### element/flash/
Flash message templates for different message types:
- `default.php`: Generic flash messages
- `success.php`: Success notifications
- `error.php`: Error messages
- `warning.php`: Warning alerts
- `info.php`: Informational messages

**Features**:
- Click-to-dismiss functionality
- HTML escaping for security
- Customizable CSS classes
- Bootstrap-compatible styling

### element/Admin/nav.php
- **Purpose**: Admin navigation menu
- **Features**:
  - Bootstrap navbar component
  - Role-based menu items
  - Responsive design

## Email Templates

### email/html/default.php
- **Purpose**: Default HTML email template
- **Features**:
  - HTML email formatting
  - Responsive email design
  - Brand-consistent styling

### email/text/default.php
- **Purpose**: Plain text email template
- **Features**: Plain text formatting for email clients

## Error Templates

### Error/error400.php
- **Purpose**: Bad request error page (400)
- **Features**: User-friendly error explanation

### Error/error500.php
- **Purpose**: Internal server error page (500)
- **Features**: Generic error message with troubleshooting tips

## Pages Templates

### Pages/home.php
- **Purpose**: Application homepage/welcome page
- **Features**:
  - CakePHP default home page
  - Environment information display
  - Database connection verification
  - Plugin and configuration status
- **Variables**: Various system status variables

## Blog Templates

### Blog/index.php
- **Purpose**: Public blog listing for published posts
- **Features**: Tag badges, optional hero image thumbnail, responsive card grid
- **Variables**: `$posts` (array of BlogPost entities)

### Blog/view.php
- **Purpose**: Public blog post view by slug
- **Features**: Optional hero image, tag badges, body rendered as paragraphs
- **Variables**: `$post` (BlogPost entity)

## Admin Blog Templates

### Admin/BlogPosts/index.php
- **Purpose**: Admin listing for all posts (draft/published)
- **Variables**: `$posts` (array of BlogPost entities)

### Admin/BlogPosts/edit.php
- **Purpose**: Shared add/edit form for blog posts
- **Features**: TinyMCE editor, hero/inline image selection, tag selection element
- **Variables**: `$post` plus select-option arrays for tag selection

## Best Practices

### Template Documentation Standards
1. **File Headers**: Include purpose and variable documentation
2. **Variable Types**: Document all template variables with `@var` annotations
3. **Features**: List key functionality and UI components
4. **Dependencies**: Note required CSS/JS frameworks

### Bootstrap Integration
1. **CSS Framework**: Bootstrap 5.3.2 loaded via CDN
2. **Icons**: Bootstrap Icons 1.11.3 for UI elements
3. **Components**: Use Bootstrap classes for consistent styling
4. **Responsive**: Mobile-first responsive design

### Security Considerations
1. **HTML Escaping**: Use `h()` function for user data output
2. **CSRF Protection**: Forms include CSRF tokens automatically
3. **Integrity Hashes**: CDN resources verified with SHA384 hashes
4. **Input Validation**: Client-side validation with server-side backup

### Performance Optimization
1. **CDN Resources**: External CSS/JS loaded from CDN
2. **Minified Assets**: Use minified versions of libraries
3. **Caching**: Template caching enabled in production
4. **Lazy Loading**: Consider lazy loading for non-critical resources

## Template Variables Reference

### Common Variables Available in All Templates
- `$this`: AppView instance with helper methods
- `$this->request`: Current request object
- `$this->response`: Current response object
- `$this->getRequest()->getAttribute('identity')`: Current authenticated user

### Layout-Specific Variables
- **default.php**: Navigation state, flash messages
- **admin.php**: Admin-specific navigation, permissions
- **error.php**: Error details, exception information

### Controller-Specific Variables
- **Users templates**: User entities, form data, validation errors
- **Admin templates**: Administrative data, user lists, dashboard metrics
- **Pages templates**: Static content, system information

## Maintenance Notes

### Regular Updates Required
1. **CDN Integrity Hashes**: Verify hashes when updating Bootstrap versions
2. **Security Headers**: Review CSP and integrity attributes
3. **Responsive Testing**: Test layouts on various screen sizes
4. **Accessibility**: Ensure WCAG compliance for forms and navigation

### Version Dependencies
- **CakePHP**: 5.x framework compatibility
- **Bootstrap**: 5.3.2 (CSS framework)
- **jQuery**: 3.7.1 (JavaScript library)
- **Bootstrap Icons**: 1.11.3 (icon library)

### File Naming Conventions
- **Layouts**: Descriptive names (`default.php`, `admin.php`)
- **Controller Templates**: Match controller actions exactly
- **Elements**: Descriptive, reusable component names
- **Email**: Separate HTML and text versions
