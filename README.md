# OpenCart - Newsman Newsletter Sync
[NewsMAN](https://www.newsman.com) plugin for OpenCart. Sync your OpenCart customers / subscribers to Newsman list / segments.

This is the easiest way to connect your Shop with Newsman.
Generate an API KEY in your Newsman account, install this plugin and you will be able to sync your shop customers and newsletter subscribers with Newsman list / segments.

![image](https://raw.githubusercontent.com/Newsman/OpenCart-Newsman/master/assets/newsmanBr.jpg)

# Installation
Manual installation:
1.  Copy contents of the uploads folder and paste to your opencart root directory
2.	Give priveleges to your user in admin->System->Users->User Groups
3.  Go to admin->Modules and then install Newsman Newsletter Sync module
4.  After installation edit the Newsman Newsletter Sync module

# Setup

1. The process is automated, login with Newsman via Oauth and the settings will get automatically filled based on your selection

![image](https://raw.githubusercontent.com/Newsman/OpenCart-Newsman/master/assets/oauth1.png)
![image](https://raw.githubusercontent.com/Newsman/OpenCart-Newsman/master/assets/oauth2.png)

(Optional)
2. Fill in your Newsman API KEY and User ID and click connect
![](https://raw.githubusercontent.com/Newsman/OpenCart-Newsman/master/assets/api-setup-screen-opencart.png)

3. Choose destination segments for your newsletter subscribers and customer groups
All your groups will be listed and you can select the Newsman Segment to map to.
You can also choose to ignore the group or to upload the group members but include them in any segment.
For the segments to show up in this form, you need to set them up in your Newsman account first.
![](https://raw.githubusercontent.com/Newsman/OpenCart-Newsman/master/assets/mapping-screen-opencart.png)

4. VQMOD Installer is required for Newsman Remarketing

Follow installation instructions
https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=22378

For the automatic synchronization to work, you must setup a webcron to run this URL:
`https://yourshop/index.php?route=module/newsman_import&cron=true`

# Description

Improve your Opencart 1.x experience with the user-friendly email marketing and sms NewsMAN Plugin – a tool designed to simplify the management of subscription forms, contact lists, newsletters, email campaigns, SMS functions, and analytics. All these tasks are easily handled through the intuitive NewsMAN email marketing and sms platform.

# Subscription Forms & Pop-ups
Design eye-catching forms and pop-ups, capturing potential leads with embedded newsletter signups or exit-intent popups.
Keep forms consistent across devices for a seamless user experience.
Integrate forms with automations for quick responses and welcome emails.

# Contact Lists & Segments

Automatically import and sync contact lists from various sources for easy data management.
Use segmentation techniques to target specific audience segments based on demographics or behavior.

# Email & SMS Marketing Campaigns 

Send mass campaigns, newsletters, or promotions to a broad subscriber base effortlessly.
Personalize campaigns for individual subscribers, addressing them by name and suggesting relevant products.
Re-engage subscribers by resending campaigns to those who haven't opened the initial email.

# Email & SMS Marketing Automation

Automate personalized product suggestions, follow-up emails, and cart abandonment strategies.
Address cart abandonment strategically or showcase related products to encourage finalizing purchases.
Gather post-purchase feedback for customer satisfaction.

# Ecommerce Remarketing

Reconnect with subscribers through targeted offers based on past interactions.
Personalize interactions with exclusive offers or reminders based on user behavior or preferences.

# SMTP Transactional Emails

Ensure prompt and reliable delivery of critical messages, such as order confirmations or shipping notifications, through SMTP.
Extended Email and SMS Statistics
Gain insights into open rates, click-through rates, conversion rates, and overall campaign performance for informed decision-making.

The NewsMAN Plugin simplifies your marketing efforts without hassle, making it easier for you to connect with your audience effectively.