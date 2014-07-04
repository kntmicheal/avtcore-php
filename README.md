avtcore-php
===========
Many of our website projects need to be created from scratch. These are small and lightweight projects which need to be very fast. Furtheron nearly every project needs an user authentication mechanism and a persistence layer.

Because we wanted to setup such projects in a couple of minutes and we did not want to install such heavy frameworks like Zend, we decided to create the avtcore-php.

Here you have a small and extensible framework which is easy to understand and to get started, even if you are new to PHP programming.

The currently implemented features are:

1. Datatable structure to represent a database table in code
2. Persistence layer which can handle MySQL, Microsoft SQL server and Oracle databases and easily can process datatables
3. CSV converter for generating CSV content from datatables and vice versa
4. CSV webservice to transfer database content over the net, supports pushing database content to remote host and querying content via SQL statements from the remote server

Currently we use the framework to synchronize tables between an Oracle and a Microsoft SQL server database, each of them on different machines running different operating systems.

<a href="http://avtnet.avorium.de:8111/viewType.html?buildTypeId=bt28&guest=1"><img src="http://avtnet.avorium.de:8111/app/rest/builds/buildType:(id:bt28)/statusIcon"/></a>
