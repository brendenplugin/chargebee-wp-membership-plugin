# Chargebee Membership WordPress Plugin

This plugin is a contribution made by an external dev agency that specializes in all things Wordpress. Here's what you can do with it:

* Sync Plans from Chargebee to WordPress.
* Use built-in login and registration pages.
* Allow automatic Customer Portal access to all registered users via SSO.
* Sync Customer and Subscription creation details from Wordpress to Chargebee.
* Insert shortcodes to show/hide content or forms.
* Configure Levels:This can be chosen if your pricing model is an ordered set of membership levels (plans). Restriction can be based on levels, and multiple plans can be assigned to each level.
* Create multiple Subscriptions for a Customer.

Please note that this is a Beta version, so you might find things that don't work perfectly. :) If you find something that has to be fixed, please file an issue in the Git repo.

If you’ve added new functionalities that you think might be helpful for all, do send us a pull request. Your contribution would be greatly appreciated!

## Prerequisites
In order to run the plugin, we recommend the following host support:
* Wordpress version greater than 4.5.0
* PHP version 5.5.9 or greater
* MySQL version 5.6 or greater OR MariaDB version 10.0 or greater

## Installation
1. Navigate to  **Plugins>Add New** in your Wordpress dashboard and click on Upload Plugin button shown on top of the page. 
2. Upload the zip of the plugin mentioned and click on “Install Now” button.
3. WordPress will automatically transfer the files and you will see a screen where it says "Plugin installed successfully". Select the  **Activate Plugin link** below.
4. Upon activating, you will be redirected to the plugin’s listing page where a message from Chargebee will be displayed. Click on the message to start the setup process.
5. You will then be directed to Chargebee’s Settings page.

## Settings
* Integration
   1. Enter your “Chargebee site name” and “Chargebee API Key” under the “Integration” tab. It is necessary to complete this step as the settings under the other tabs won’t be accessible otherwise. The API key can be retrieved from your Chargebee site under **Settings> API & Webhooks> API Keys**.
  2. **Authentication** Follow these steps below to authenticate your plugin:
      * When copying the webhook url from the plugin to your Chargebee site, enable the checkbox My webhook URL is protected by basic authentication under Settings> API & Webhooks> Webhooks Settings.
      * Copy the username and password found in the plugin under Settings> Integrations to your Chargebee site.
      * After adding your sitename and Chargebee API key, click on Save API key & synchronize button.
      **Note**: Please try with  Chargebee’s TEST site. If you’ve tested out your use cases and are satisfied with the plugin, you can go ahead and sync your LIVE site.
      * As soon as a valid site name and API key are entered and the sync button is selected,  Plans (if any) from your Chargebee site will be imported into WordPress. The synced Products can be viewed under  Chargebee > Products.  
       **Important**: Once this is done, go to Settings > Permalinks and click on the Save Changes button. 
* **Pages**
    Chargebee Membership plugin automatically creates login, registration and thank you pages after activation. If you wish to use different WordPress pages, you can create those and select them from the dropdown option.
**Note**: The pages will not be shown if you’re viewing them as an admin. Please ensure that the pages are opened in cognito.

* **Account**
     1. Chargebee Login Page: 
If **Force Wordpress to use the Chargebee login page** is checked then the user will be forced to use Chargebee Login page which 
was set under "Pages" tab & "/wp-admin" or "/wp-login.php" URLs will not work. They will redirect to Login page. If it is unchecked, then WordPress login form will be used.
    2. URL to redirect after login: 
This setting determines where to redirect the customer after login.  Insert a  URL without site’s name. Example: For
http://example.com/test-123 page only enter test-123 in the text field.

* **General**
   1. Default Membership Plan
This option can be used to automatically assign registered users to a default free plan. 
   2. Restricted Content Message 
When content is restricted to a specific Level and a user who doesn’t belong to that level tries to access the content, this message will be displayed.  User’s level will be fetched dynamically using {user_level} and the rest of the content will be displayed there as you’ve set.

## Products
1. The products (Plans) imported at the time of set up will be accessible under **Chargebee > Products** menu.
2. All  details except *product description* will be in read-only form. Once you’ve completed the above steps, you can view the product page by clicking on the *View* link for the particular product. 
3. You will see the product page and the Subscribe button after the content. After successful checkout, user will be directed to the Thank You page set under **Settings > Pages > Thank You Page**.

## Levels
1. If you’d like to restrict content based on membership levels, you can group Chargebee’s plans to Levels under **Chargebee > Levels**.
2. Multiple Products  can be selected for a single level.

## Content Restriction
1. Content restrictions can be set in 3 ways: 
    * By assigning a Level to a category & then assigning that category to post(s).
    * By assigning a Level to a page/post from within the page/post screen using “Chargebee Content Restriction” meta box. 
     or
    * By using shortcodes in the content.

2. How content restriction priority works:
   In the “Chargebee Content Restriction” meta box, if you select:
   * Everyone then all the content will be visible to everyone irrespective of category assigned or shortcodes used within the content.
   * As Restricted at Category Level,  then the  content will be visible to those users who have subscribed to any plan available in a Level  that’s been assigned to the WordPress default category/categories.
   * Selected Level, then the content will be visible to those users who have subscribed to any plan that is available in the level selected.
   * As per content shortcode, then the content will only respect the restriction you’ve enforced using **[cb_content_show level=”level_id”]** or **[cb_content_hide level=”level_id”]** within the content. The Level ID can be referred from **Chargebee > Levels** listing table. If you do not mention level for **[cb_content_show]** shortcode then the content will be shown to all users. Similarly, if you do not mention level for **[cb_content_hide]** shortcode then content will be hidden for all users. 


## List of Shortcodes

*  **Account link shortcode**  
    Will output a link which will be visible only to logged in user. Clicking on it will open a link to the customer portal in a new tab.
	**[cb_account_link]Account[/cb_account_link]**

* **Update Payment Method Form shortcode** 
    Will output a link to update payment method form for logged in user. Clicking on a link will open the update payment method hosted form page in new tab.
	**[cb_update_payment_method_form width=800 height=500]**

* **Registration form shortcode**  
    Will show the registration form to non-logged in user.
     **[cb_registration_form]**

* **Login/Logout link shortcode**  
    Will show login link to non-loggedin user and logout link to logged in user.
	**[cb_login_logout_link]**

* **Display cvvc ions shortcode** 
    Will display subscriptions to logged in user.
    **[cb_display_subscription]**

* **Not logged in shortcode** 
    Will show the content within shortcode to non logged in user.
	**[cb_not_logged_in]** Non logged in users can only see this text.**[/cb_not_logged_in]**

* **Paid subscription shortcode** 
    Will show the content within this shortcode to any user who has paid subscription irrespective of  purchased plan. 
	**[cb_paid_subscription]**.The current user has an active subscription.**[/cb_paid_subscription]**

* **Free Subscription shortcode** 
    Will show the content within this shortcode to users having free subscription only.
[cb_free_subscription]Logged-in users who do not have a paid subscription.[/cb_free_subscription]

* **Show / Hide Content** 
    This shortcode is used to show/hide content for specific levels. We can use level values to show or hide content to users based on their subscription plans.
    For example: To show content to users who’ve plan/product associated with Level 1 we can use:
**[cb_content_show level="1"]** This content will be shown to any users who have
plan associated with Level 1 **[/cb_content_show]**
To hide content from users who’ve plan associated with Level 4 we can use : 
**[cb_content_hide level="4"]** This content will be hidden for those users who have plan associated with Level 4 **[/cb_content_hide]** 

* **Subscribe button**
    This shortcode can be used to display a product’s subscribe button in a particular post or page. **[cb_product_subscribe   product_id=""]** Using this, subscribe buttons for multiple products can also be shown in one page.

## Limitations
1. Plans alone can be synced from Chargebee to Wordpress. 
2. Pricing table is not supported right now. However, if you’d like to display multiple products in one page, you can add the Subscribe shortcode for each product onto the page. This way, your customers can checkout directly from this page and the data will be linked to Wordpress & Chargebee.
3. This version supports single sites only.
