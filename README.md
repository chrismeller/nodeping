Description
===========
NodePing ([nodeping.com](http://nodeping.com)) is an amazing server monitoring service with a great price for more checks than the average user could ever aspire to use. I've used it for almost a year now to monitor dozens of sites and servers, but only recently wanted to write a status dashboard to aggregate these results in an easily-skimmed visual way. After a lot of trial and error and deciphering of their documentation (which could be better), here's the result.

You can use this basic class to get a list of the accounts (your primary and any sub-accounts) and checks associated with your account, as well as fetch results for any of your checks. It's very basic and there's a lot of room for improvement and many more API calls it could support, but hopefully it'll get you started.

License
-------

	Copyright 2012 Chris Meller

	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at

	    http://www.apache.org/licenses/LICENSE-2.0

	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License.

Usage
=====

Include the class in your code, make sure your API token is handy, and get started:

````$checks = Nodeping::factory( $token )->get_checks();````

The result will be an array of Nodeping_Check objects that you can easily iterate over. Check the PHPDoc comments for descriptions of the properties of the object, some of them aren't immediately obvious.

You can also specify the second optional ``$account`` parameter to the factory or constructor and all results should be limited to that account (for example, if you're masquerading as one of your clients and only want to view the checks configured on their sub-account).

A Word of Warning
=================

The Nodeping API is not the fastest beast in the world. Particularly when getting results for a check (even a relatively small number of them) you can easily spend several seconds waiting for a result. You should **never** make these calls on a page load. You'll need a backend process that runs via cron to update a local cache.