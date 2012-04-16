#Solr dataservice for [Lithium PHP](http://lithify.me)

The orginazation I work for has recently began using Apache's [Solr](http://lucene.apache.org/solr/).
My goal was to make using Solr feel exactly the same as using any other data service in Lithium.

## Installation
1. Clone/Download the plugin into your app's ``libraries`` directory.
2. Tell your app to load the plugin by adding the following to your app's ``config/bootstrap/libraries.php``:

        Libraries::add('li3_solr');

## Connect To Solr

~~~ php
Connections::add('search', array(

	'type' => 'http',
	'adapter' => 'Solr',
	'host' => '10.10.1.123',
	'core' => 'products'

));
~~~

## Basic Usage

In your model or controller create a `Model::find`

~~~ php
$entertainment = Product::find('all', array(

	'query' => "manufacturer:Sony AND (product:TV OR product:MP3 Player)",
		
	'facets' => array('capacity' => 'disc_size', 'screensize' => 'dimensions'),

	'fields' => array('id', 'dimensions','disc_size', 'store', 'quantity', 'price', 'product')

));

$sony = $entertainment->to('array');
~~~

The above request would create an array assigned to the variable $sony that would contain all Sony televisions and MP3 Players.

It would create 2 facets, one on disc size (for the MP3 Players) and one for screen size (televisions). You could then use this information to drill down your requests.

Finally, it would only return details such as the products `id`, `dimensions`, `disc size`, `store` name, `quantity` in stock, `price` and `product` type.

## Other Options

Along with `query`, `facets` and `fields` you can also pass in `limit`. (`"limit" => 5`) This will return only the first 5 results.

Like `limit` you can also pass in a range:

~~~ php
'range' => array(
	'start' => 6,
	'end' => 17,
)
~~~

This will return 11 results, starting at row 6 and ending at 17.

You can also tell the query where to start and give it a number of rows to walk:

~~~ php
'range' => array(
        'start' => 6,
	'length' => 11, // tell the request to only get 11 rows
)
~~~

This will return exactly the same as the first range, only you didn't have to define exactly which row to end with.

You can use `range` as a smarter way to limit results. `limit` always starts with the first record coming from your query but `range` can begin anywhere.

## Things to come ...

My goal is to be able to pass the request a nicely formatted array of conditions and have it be translated into a solr query. I haven't had the time to perfect this yet, however.

Example of what I'd like (would result in the same query as the above example):

~~~ php
'conditions' => array(
	'manufacturer' => 'Sony',
	'OR' => array(
		'product' => array('TV', 'MP3 Player')
	)
)
~~~

Obviously this is a simple example and would be easy to accomplish, however I also want to be able to account for complex, nested subqueries.

This dataservice does not yet support `creates`, `updates` or `deletes`. These will be added shortly.

## Credits

- [Lithium PHP](http://lithify.me)
- Solr PHP help by the Awesome [Solarium](www.solarium-project.org) PHP Library.
- Solr Queries (to be used with query builder) from the handy PHP class [SolrQueryBuilder](https://bitbucket.org/wneeds/solrquerybuilder)

## Call for help

If you feel this is a project worth your effort I welcome and request your help. 

Feel free to fork and send pull requests!

This project is still in its infancy. I am not a solr expert. As I mentioned earlier, this project was spawned based on a need from my employer and I had no solr experiance beforehand.
