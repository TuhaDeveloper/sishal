# Banner Management System

This document explains how to use the banner management system in your ERP.

## Features

- **Complete CRUD Operations**: Create, read, update, and delete banners
- **Image Upload**: Support for JPEG, PNG, JPG, GIF, and WebP formats
- **Position Management**: Place banners in different positions (top, middle, bottom, sidebar)
- **Scheduling**: Set start and end dates for banner display
- **Status Control**: Activate/deactivate banners instantly
- **Sort Order**: Control the display order of banners
- **Link Support**: Add clickable links to banners
- **Responsive Design**: Mobile-friendly banner display

## Database Structure

The `banners` table includes:
- `title`: Banner title (required)
- `description`: Banner description (optional)
- `image`: Image file path (optional)
- `position`: Display position (top, middle, bottom, sidebar)
- `status`: Active/Inactive status
- `start_date`: When banner becomes active (optional)
- `end_date`: When banner expires (optional)
- `link_url`: Clickable link URL (optional)
- `link_text`: Link button text (optional)
- `sort_order`: Display order (0 = first)

## Usage

### 1. Access Banner Management

Navigate to **Banner Management** in the ERP sidebar to:
- View all banners
- Create new banners
- Edit existing banners
- Delete banners
- Toggle banner status

### 2. Creating Banners

1. Click "Add Banner" button
2. Fill in the required fields:
   - **Title**: Banner title
   - **Position**: Where to display the banner
   - **Status**: Active or Inactive
3. Optional fields:
   - **Description**: Banner description
   - **Image**: Upload banner image
   - **Link URL**: Where to redirect when clicked
   - **Link Text**: Button text for the link
   - **Start Date**: When banner becomes active
   - **End Date**: When banner expires
   - **Sort Order**: Display order (lower numbers appear first)

### 3. Displaying Banners on Frontend

Use the banner display component in your Blade templates:

```blade
{{-- Display top banners --}}
@include('components.banner-display', ['position' => 'top'])

{{-- Display sidebar banners --}}
@include('components.banner-display', ['position' => 'sidebar'])

{{-- Display middle banners --}}
@include('components.banner-display', ['position' => 'middle'])

{{-- Display bottom banners --}}
@include('components.banner-display', ['position' => 'bottom'])
```

### 4. Banner Positions

- **Top**: Typically used for main promotional banners
- **Middle**: For content-related banners
- **Bottom**: For footer banners or additional promotions
- **Sidebar**: For sidebar advertisements or quick links

### 5. Banner Status

- **Active**: Banner is currently displayed (if within date range)
- **Inactive**: Banner is hidden from display

### 6. Date Scheduling

- **Start Date**: Banner becomes active at this date/time
- **End Date**: Banner expires at this date/time
- If no dates are set, banner is active immediately (if status is active)

## Permissions

The system includes the following permissions:
- `view banners`: View banner management page
- `create banners`: Create new banners
- `edit banners`: Edit existing banners
- `delete banners`: Delete banners

## File Storage

Banner images are stored in `storage/app/public/banners/` and accessible via the `storage` link.

## API Endpoints

- `GET /erp/banners` - List all banners
- `GET /erp/banners/create` - Show create form
- `POST /erp/banners` - Store new banner
- `GET /erp/banners/{id}` - Show banner details
- `GET /erp/banners/{id}/edit` - Show edit form
- `PUT /erp/banners/{id}` - Update banner
- `DELETE /erp/banners/{id}` - Delete banner
- `PATCH /erp/banners/{id}/toggle-status` - Toggle banner status

## Best Practices

1. **Image Optimization**: Use appropriately sized images (recommended max width: 1200px)
2. **File Size**: Keep images under 2MB for better performance
3. **Alt Text**: Always provide meaningful titles for accessibility
4. **Date Management**: Set appropriate start/end dates for time-sensitive banners
5. **Sort Order**: Use consistent numbering (0, 10, 20, 30...) for easy reordering
6. **Testing**: Test banners on different screen sizes and devices

## Troubleshooting

- **Images not displaying**: Check if the storage link is created (`php artisan storage:link`)
- **Banners not showing**: Verify banner status is "Active" and within date range
- **Permission errors**: Ensure user has appropriate banner management permissions
- **Upload errors**: Check file size (max 2MB) and format (JPEG, PNG, JPG, GIF, WebP)

## Future Enhancements

Potential improvements for the banner system:
- Banner analytics and click tracking
- A/B testing for different banner versions
- Banner templates and themes
- Bulk operations (activate/deactivate multiple banners)
- Banner categories and tags
- Advanced scheduling options (recurring banners)
