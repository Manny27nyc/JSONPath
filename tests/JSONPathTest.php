<?php
namespace Flow\JSONPath\Test;

require_once __DIR__ . "/../vendor/autoload.php";

use Flow\JSONPath\JSONPath;
use Flow\JSONPath\JSONPathLexer;
use \Peekmo\JsonPath\JsonPath as PeekmoJsonPath;

class JSONPathTest extends \PHPUnit_Framework_TestCase
{

    /**
     * $.store.books[0].title
     */
    public function testFilter_Index()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find('$.store.books[0].title');
        $this->assertEquals('Sayings of the Century', $result[0]);
    }

    /**
     * $['store']['books'][0]['title']
     */
    public function testFilter_IndexWithQuotes()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$['store']['books'][0][\"title\"]");
        $this->assertEquals('Sayings of the Century', $result[0]);
    }

    /**
     * $.array[start:end:step]
     */
    public function testFilter_Slice_1()
    {
        // Copy all items... similar to a wildcard
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$['store']['books'][:].title");
        $this->assertEquals(['Sayings of the Century', 'Sword of Honour', 'Moby Dick', 'The Lord of the Rings'], $result->data());
    }

    public function testFilter_Slice_2()
    {
        // Fetch every second item starting with the first index (odd items)
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$['store']['books'][1::2].title");
        $this->assertEquals(['Sword of Honour', 'The Lord of the Rings'], $result->data());
    }

    public function testFilter_Slice_3()
    {
        // Fetch up to the second index
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$['store']['books'][0:2:1].title");
        $this->assertEquals(['Sayings of the Century', 'Sword of Honour', 'Moby Dick'], $result->data());
    }

    public function testFilter_Slice_4()
    {
        // Fetch up to the second index
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$['store']['books'][-1:].title");
        $this->assertEquals(['The Lord of the Rings'], $result->data());
    }

    /**
     * Everything except the last 2 items
     */
    public function testFilter_Slice_5()
    {
        // Fetch up to the second index
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$['store']['books'][:-2].title");
        $this->assertEquals(['Sayings of the Century', 'Sword of Honour'], $result->data());
    }

    /**
     * The Last item
     */
    public function testFilter_Slice_6()
    {
        // Fetch up to the second index
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$['store']['books'][-1].title");
        $this->assertEquals(['The Lord of the Rings'], $result->data());
    }

    /**
     * $.store.books[(@.length-1)].title
     *
     * This notation is only partially implemented eg. hacked in
     */
    public function testFilter_QueryResult()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$.store.books[(@.length-1)].title");
        $this->assertEquals(['The Lord of the Rings'], $result->data());
    }

    /**
     * $.store.books[?(@.price < 10)].title
     * Filter books that have a price less than 10
     */
    public function testFilter_QueryMatch_LessThan()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$.store.books[?(@.price < 10)].title");
        $this->assertEquals(['Sayings of the Century', 'Moby Dick'], $result->data());
    }

    /**
     * $..books[?(@.author == "J. R. R. Tolkien")]
     * Filter books that have a title equal to "..."
     */
    public function testFilter_QueryMatch_Equals()
    {
        $results = (new JSONPath($this->exampleData(rand(0, 1))))->find('$..books[?(@.author == "J. R. R. Tolkien")].title');
        $this->assertEquals($results[0], 'The Lord of the Rings');
    }

    /**
     * $.store.books[*].author
     */
    public function testFilter_Wildcard_SquareBrackets()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$.store.books[*].author");
        $this->assertEquals(['Nigel Rees', 'Evelyn Waugh', 'Herman Melville', 'J. R. R. Tolkien'], $result->data());
    }

    /**
     * $..author
     */
    public function testFilter_Recursive_Index()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$..author");
        $this->assertEquals(['Nigel Rees', 'Evelyn Waugh', 'Herman Melville', 'J. R. R. Tolkien'], $result->data());
    }

    /**
     * $.store.*
     * all things in store
     * the structure of the example data makes this test look weird
     */
    public function testFilter_Wildcard()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$.store.*");
        if (is_object($result[0][0])) {
            $this->assertEquals('Sayings of the Century', $result[0][0]->title);
        } else {
            $this->assertEquals('Sayings of the Century', $result[0][0]['title']);
        }

        if (is_object($result[1])) {
            $this->assertEquals('red', $result[1]->color);
        } else {
            $this->assertEquals('red', $result[1]['color']);
        }
    }

    /**
     * $.store..price
     * the price of everything in the store.
     */
    public function testFilter_Recursive_Index_2()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$.store..price");
        $this->assertEquals([8.95, 12.99, 8.99, 22.99, 19.95], $result->data());
    }

    /**
     * $..books[2]
     * the third book
     */
    public function testFilter_RecursiveChildSearchWithChildIndex()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$..books[2].title");
        $this->assertEquals(["Moby Dick"], $result->data());
    }

    /**
     * $..books[(@.length-1)]
     */
    public function testFilter_RecursiveChildSearchWithChildQuery()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$..books[(@.length-1)].title");
        $this->assertEquals(["The Lord of the Rings"], $result->data());
    }

    /**
     * $..books[-1:]
     * Resturn the last results
     */
    public function testFilter_RecursiveChildSearchWithSliceFilter()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$..books[-1:].title");
        $this->assertEquals(["The Lord of the Rings"], $result->data());
    }

    /**
     * $..books[?(@.isbn)]
     * filter all books with isbn number
     */
    public function testFilter_RecursiveWithQueryMatch()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$..books[?(@.isbn)].isbn");

        $this->assertEquals(['0-553-21311-3', '0-395-19395-8'], $result->data());
    }

    /**
     * $..*
     * All members of JSON structure
     */
    public function testFilter_RecursiveWithWildcard()
    {
        $result = (new JSONPath($this->exampleData(rand(0, 1))))->find("$..*");
        $result = json_decode(json_encode($result), true);

        $this->assertEquals('Sayings of the Century', $result[0]['books'][0]['title']);
        $this->assertEquals(19.95, $result[26]);
    }

    /**
     * Tests direct key access.
     */
    public function testFilter_SimpleArrayAccess()
    {
        $result = (new JSONPath(array('title' => 'test title')))->find('title');

        $this->assertEquals(array('test title'), $result->data());
    }

    public function testFilter_OnNoneArrays()
    {
        $data = ['foo' => 'asdf'];

        $result = (new JSONPath($data))->find("$.foo.bar");
        $this->assertEquals([], $result->data());
    }


    public function testFilter_MagicMethods()
    {
        $fooClass = new JSONPathTestClass();

        $results = (new JSONPath($fooClass, JSONPath::ALLOW_MAGIC))->find('$.foo');

        $this->assertEquals(['bar'], $results->data());
    }


    public function testFilter_MatchWithComplexSquareBrackets()
    {
        $result = (new JSONPath($this->exampleDataExtra()))->find("$['http://www.w3.org/2000/01/rdf-schema#label'][?(@['@language']='en')]['@language']");
        $this->assertEquals(["en"], $result->data());
    }

    public function testFilter_QueryMatchWithRecursive()
    {
        $locations = $this->exampleDataLocations();
        $result = (new JSONPath($locations))->find("..[?(@.type == 'suburb')].name");
        $this->assertEquals(["Rosebank"], $result->data());
    }

    public function testFilter_First()
    {
        $result = (new JSONPath($this->exampleDataExtra()))->find("$['http://www.w3.org/2000/01/rdf-schema#label'].*");

        $this->assertEquals(["@language" => "en"], $result->first()->data());
    }

    public function testFilter_Last()
    {
        $result = (new JSONPath($this->exampleDataExtra()))->find("$['http://www.w3.org/2000/01/rdf-schema#label'].*");
        $this->assertEquals(["@language" => "de"], $result->last()->data());
    }

    public function exampleData($asArray = true)
    {
        $json = '
        {
          "store":{
            "books":[
              {
                "category":"reference",
                "author":"Nigel Rees",
                "title":"Sayings of the Century",
                "price":8.95
              },
              {
                "category":"fiction",
                "author":"Evelyn Waugh",
                "title":"Sword of Honour",
                "price":12.99
              },
              {
                "category":"fiction",
                "author":"Herman Melville",
                "title":"Moby Dick",
                "isbn":"0-553-21311-3",
                "price":8.99
              },
              {
                "category":"fiction",
                "author":"J. R. R. Tolkien",
                "title":"The Lord of the Rings",
                "isbn":"0-395-19395-8",
                "price":22.99
              }
            ],
            "bicycle":{
              "color":"red",
              "price":19.95
            }
          }
        }';
        return json_decode($json, $asArray);
    }

    public function exampleDataExtra($asArray = true)
    {
        $json = '
            {
               "http://www.w3.org/2000/01/rdf-schema#label":[
                  {
                     "@language":"en"
                  },
                  {
                     "@language":"de"
                  }
               ]
            }
        ';

        return json_decode($json, $asArray);
    }


    public function exampleDataLocations($asArray = true)
    {
        $json = '
            {
               "name": "Gauteng",
               "type": "province",
               "child": {
                    "name": "Johannesburg",
                    "type": "city",
                    "child": {
                        "name": "Rosebank",
                        "type": "suburb"
                    }
               }
            }
        ';

        return json_decode($json, $asArray);
    }



}

class JSONPathTestClass
{
    protected $attributes = [
        'foo' => 'bar'
    ];

    public function __get($key)
    {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }
}
