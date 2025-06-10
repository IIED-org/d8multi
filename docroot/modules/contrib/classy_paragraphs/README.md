# Classy paragraphs

## Table of contents

- Overview
- Requirements
- Installation
- How it works
- Quick start
- Detailed configuration
- Maintainers


## Overview

Classy paragraphs ships a new field type "Class list" which allows an editor to
apply a selected class to paragraphs via a drop-down list.


## Requirements

- [Paragraphs](https://www.drupal.org/project/paragraphs)


## Installation

Install as you would normally install a contributed Drupal module. See
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules for further
information.


## How it works

We've refactored the module so it uses Drupal 8's new config entity. By doing
this we can export CSS classes (called styles), we get a UI to manage the
classes and NO MORE hooks. Also, you can add multiple classes to a single style.

By default, the class list will added to the `{{ attributes.class }}` Twig
variable array. Insure this is added to your twig template markup. Note: if `{{
attributes }}` is already in your template, there is no need to add the
additional .class object.

NOTE: The module won't work if you're using Display Suite or Panelizer to
configure the paragraphs. Integration will come later.


## Quick start

1. Go to `/admin/structure/classy_paragraphs_style` and create a style (set of
classes).
1. Add new "Reference" field on Paragraph Type using "Reference > Other..."
1. Select type of item to reference: "Configuration > Classy Paragraphs Style"

## Detailed configuration

1. Go to the Classy paragraphs configuration page at Admin Menu > Structure > Classy paragraphs style (`/admin/structure/classy_paragraphs_style`)
1. Add some styles, you can add a single class at a time or a grouping of classes. For example: a background color (single) or a default set of responsive widths for a card element (width 50% desktop, 50% tablet, 100% on mobile)
    - click the **+ Add Classy paragraphs style** button
    - The **label** field is what the content builder/admin is going to see when creating paragraphs in the back end
    - The **classes** field will hold the classes you're going to add to the paragraph, add one per line
    - **Save** the Style
    - After adding new Styles in the Classy paragraphs config page, clear your cache for them to become available on the front end
1. Edit your Paragraph's fields (under Admin Menu > Structure > Paragraphs (`/admin/structure/paragraphs_type`)
    - Go to the Manage Fields page for the paragraph you want
        - Add a new **Reference Field**
        - Choose Reference > **Other...**
        - Name the field for example "Paragraph Classes" or what makes sense
        - Under **Type of item to reference** choose Configuration > **Classy paragraphs style**
        - Under **Allowed number of values**, choose **Unlimited** items
        - **Save** the field settings
        - On the Field Edit page, verify that the field name is what you want site builders to see, add help text if you want, and click **Save Settings**
        - The field has now been added to your field list
    - Now head over to the **Manage form display** page/tab
        - Place/Order the Classy Paragraph's reference field where you want it in your field list
        - Select the type of widget you want to use, for example "checkboxes" or "select list" so site builders can see all the options available to them
        - Save the Manage form display page
    - Go to the **Manage display** page/tab
        - Place and order the Classy paragraph's reference field in the **Disabled** section
        - Save the Manage display page
    - If you don't use customized Twig templates for the paragraphs on your site, you should be done. Add some paragraphs with classes, refresh the front end and you should see your classes show up.

### Customized Twig templates

If you use customized Twig templates for your paragraphs, do this for local development:

- **Note that this is what you need to check to make sure Classy paragraphs will work properly for you**
- Enable your `services.yml` file in your `/web/sites/default` folder, make sure it has Twig debug turned on
- Clear your cache
- Now when you inspect the code on the front-end of your site, you should be seeing the HTML comments telling you the location and name of the Twig file you care about. Create a new Twig file if you need to get more specific. This guide isn't going to go into a ton of Twig detail, that's for other tutorials which are easy to find
- (_Example below_) In the file, there should be a div with `{{ attributes.addClass(classes) }}` as part of it. This variable is what's going to pick up the Classy Paragraphs classes, so if you've removed it or hidden it in your file, you may need to do some work to add it back in. It cannot be included in the lines: `{% block paragraph %}` or `{% block content %}`. It has to be on an HTML element
- Save the Twig file, upload it back to your site
- Clear the cache: `drush cache:rebuild`
- Refresh the front of the website and everything should be all good.

#### A rough example of a paragraph's twig file:

Please verify with what Twig is showing on your site. This one may be different than your site.

```
{%
  set classes = [
    'paragraph',
    'paragraph--type--' ~ paragraph.bundle|clean_class,
    view_mode ? 'paragraph--view-mode--' ~ view_mode|clean_class,
    not paragraph.isPublished() ? 'paragraph--unpublished'
  ]
%}
<div {{ attributes.addClass(classes) }}>
{% block paragraph %}
    {% block content %}
      {{ content }}
    {% endblock %}
{% endblock paragraph %}
</div>
```

After adding new Styles in the Classy Paragraphs Config page, clear your cache for them to become available on the front end.

## Maintainers

- Ivan Zugec ([ivan-zugec](https://www.drupal.org/u/ivan-zugec))
- Ivan Trokhanenko ([i-trokhanenko](https://www.drupal.org/u/i-trokhanenko))
