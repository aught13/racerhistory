# Templates & UI Elements Summary

## Layout Templates

- ✅ `layout/default.php` - Main application layout with Bootstrap 5.3.2, responsive design, and authentication

- ✅ `layout/admin.php` - Administrative interface layout with admin navigation integration

## User Authentication Templates

- ✅ `Users/login.php` - Responsive login form with password visibility toggle and validation

- ✅ `Users/register.php` - User registration form with entity binding and client-side controls

## Admin Interface Templates

 ✅ `Admin/Dashboard/index.php` - Main admin dashboard with authentication status (fixed namespace issue)
 ✅ `Admin/SiteOptions/sportsConfigs.php` - Sport configuration UI (replaces legacy Admin/Sports CRUD; managed via SiteOptions)
 ✅ `Admin/Teams/add.php` - Add team form with sport selection using SiteOptions-backed `sport_key` (popup sport creation removed)
- ✅ `Admin/BlogPosts/index.php` - Blog post listing (draft/published) with basic actions
- ✅ `Admin/BlogPosts/edit.php` - Add/edit blog post form with TinyMCE, hero/inline images, and tags

## Public Blog Templates

- ✅ `Blog/index.php` - Public blog listing of published posts
- ✅ `Blog/view.php` - Public blog post view by slug

## Flash Message Elements

- ✅ `element/flash/default.php` - Generic flash messages with customizable styling

- ✅ `element/flash/success.php` - Success notifications with positive styling

- ✅ `element/flash/error.php` - Error messages with negative styling indicators

- ✅ `element/flash/warning.php` - Warning messages with caution styling

- ✅ `element/flash/info.php` - Informational messages with neutral styling

## Navigation Elements

- ✅ `element/Admin/nav.php` - Bootstrap admin navbar with responsive design and authentication
- ✅ `element/Admin/confirm_delete.php` - Reusable confirm delete modal (trimmed minimal JS)
- ✅ `element/Admin/popup_form.php` - Reusable AJAX popup form element

## Email Templates

- ✅ `email/html/default.php` - HTML email formatting with line-by-line content processing

## Error Templates

- ✅ `Error/error400.php` - Bad request error page with environment-aware display

## Development Guidelines

### Template Creation Standards

1. **Header Requirements**: Include purpose, features, variables, and usage
2. **Variable Documentation**: Use @var annotations for all template variables
3. **Security Practices**: HTML escaping, CSRF tokens, authentication checks
4. **Bootstrap Integration**: Use Bootstrap 5.3.2 classes consistently
5. **Responsive Design**: Mobile-first approach with proper breakpoints
6. **Modal & Popup Elements**: Centralized JS logic lives in minimal elements; avoid duplication across templates.

### Maintenance Procedures

1. **CDN Updates**: Verify integrity hashes when updating Bootstrap versions

2. **Security Reviews**: Regular assessment of escape handling and CSRF protection

3. **Accessibility Testing**: Ensure WCAG compliance for forms and navigation

4. **Performance Monitoring**: Monitor CDN loading and template rendering times

## Template Architecture Overview

```text
Templates are organized in a hierarchical structure:
├── Layouts (default.php, admin.php) - Page structure
├── Controllers (Users/, Admin/) - Action-specific views
├── Elements (flash/, Admin/) - Reusable components (confirm_delete, popup_form)
├── Email (html/, text/) - Email formatting
└── Error (error400.php, error500.php) - Error pages
```

## Supplemental Documentation

Refer to `templates/POPUP_FORM_COMPONENT.md` for popup form usage and FormProtection integration.
