# OpenCart - Newsman Newsletter Sync
[Newsman](https://www.newsmanapp.com) plugin for OpenCart. Sync your OpenCart customers / subscribers to Newsman list / segments.

This is the easiest way to connect your Shop with [Newsman](https://www.newsmanapp.com).
Generate an API KEY in your [Newsman](https://www.newsmanapp.com) account, install this plugin and you will be able to sync your shop customers and newsletter subscribers with Newsman list / segments.

#Installation
Manual installation:
1.  Copy contents of the uploads folder and paste to your opencart root directory
2.	Give priveleges to your user in admin->System->Users->User Groups
3.  Go to admin->Modules and then install Newsman Newsletter Sync module
4.  After installation edit the Newsman Newsletter Sync module

#Setup
1. Fill in your Newsman API KEY and User ID and click connect
![](https://raw.githubusercontent.com/Newsman/OpenCart-Newsman/master/assets/api-setup-screen-opencart.png)

2. Choose destination segments for your newsletter subscribers and customer groups
All your groups will be listed and you can select the Newsman Segment to map to.
You can also choose to ignore the group or to upload the group members but include them in any segment.
For the segments to show up in this form, you need to set them up in your Newsman account first.
![](https://raw.githubusercontent.com/Newsman/OpenCart-Newsman/master/assets/mapping-screen-opencart.png)

For the automatic synchronization to work, you must setup a webcron to run this URL:
`http://yourshop/index.php?route=module/newsman_import`

