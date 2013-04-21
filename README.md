Google Analytics Plugin for Wordpress
================================

Overview
--------------------------------
This project is just modified script to work with the latest version of
Google Analytics' tracking code, which loooks as follow:


&lt;script&gt;  
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){  
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),  
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)  
  })(window,document,'script','//www.google-analytics.com/analytics.js', 'ga');  
  
  ga('create', 'UA-12345678-1', 'domain.com');  
  ga('send', 'pageview');  
&lt;/script&gt;  


Copyright
--------------------------------
Copyright (c) 2013 Tomasz [Evolic] Kuter