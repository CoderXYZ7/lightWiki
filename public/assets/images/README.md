# Logo Setup Guide

This directory is where you should place your website logo for use in the lightWiki header.

## How to Add a Logo

1. **Place your logo image** in this directory (`lightWiki/public/assets/images/`)
   - Supported formats: PNG, JPG, JPEG, GIF, SVG
   - Recommended size: 40px height (width will scale automatically)
   - Example filename: `logo.png`

2. **Update the configuration** in `lightWiki/core/config.php`:
   ```php
   // Logo settings
   'logo_path' => '/assets/images/logo.png', // Path to your logo image
   'logo_width' => '40px',                   // Logo width in CSS units
   'logo_height' => 'auto',                  // Logo height in CSS units
   ```

3. **Your logo will appear** next to your site title in the header navigation

## Logo Configuration Options

- **logo_path**: Path to your logo file (relative to public directory)
  - Leave empty `''` to use text-only header
  - Example: `'/assets/images/my-logo.svg'`

- **logo_width**: CSS width value for the logo
  - Examples: `'40px'`, `'2.5rem'`, `'auto'`

- **logo_height**: CSS height value for the logo
  - Examples: `'40px'`, `'auto'`, `'100%'`

## Best Practices

- **SVG files** are recommended for crisp display at all sizes
- **PNG files** with transparency work well for complex logos
- Keep file sizes small (under 100KB) for fast loading
- Test your logo on different themes to ensure good contrast
- Use a square or horizontal logo format for best results

## Example Logo Filenames

- `logo.png` - Simple PNG logo
- `logo.svg` - Vector logo (recommended)
- `brand-icon.png` - Company brand icon
- `site-logo.jpg` - JPEG format logo

## Troubleshooting

- **Logo not showing?** Check that the file path in config.php is correct
- **Logo too big/small?** Adjust the `logo_width` and `logo_height` values
- **Logo looks blurry?** Try using an SVG file or higher resolution PNG
- **Logo doesn't fit theme?** Consider creating different logos for light/dark themes