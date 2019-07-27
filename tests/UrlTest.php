<?php
class UrlTest extends \BaseTestCase
{
    public function caseSetUp()
    {
    }

    public function testUrl()
    {
        $url = new \Exedra\Url\Url('http://localhost/test');

        $this->assertEquals('http://localhost/test', $url);
    }

    public function testFactory()
    {
        $app = new \Exedra\Application(__DIR__);

        $app->provider->add(\Exedra\Support\Provider\Framework::class);

        $app->map['foo']->get('/bar')->execute(function(){});

        $factory = new \Exedra\Url\UrlFactory($app->map, null, 'http://localhost');

        $this->assertEquals((string) $factory->to('hello'), 'http://localhost/hello');

        $this->assertEquals((string) $factory->to('hello')->setHost('192.168.1.100'), 'http://192.168.1.100/hello');

        $this->assertEquals($factory->route('foo')->addQueryParam('baz', 'bat')->addQueryParams(array('baft' => 'jazt', 'taz' => 'tux')), 'http://localhost/bar?baz=bat&baft=jazt&taz=tux');

        $url = $factory->to('foo-bar');

        $url->setQueryParams(array(
            'foo' => 'bar',
            'baz' => array('bad')
        ));

        $url->addQueryParam('qux', array(
            'tux' => 'new',
            'eqa' => array(
                'opa' => array(
                    'gan' => 'nam'
                )
            )
        ));

        $this->assertEquals(array(
            'foo' => 'bar',
            'baz' => array('bad'),
            'qux' => array(
                'tux' => 'new',
                'eqa' => array(
                    'opa' => array(
                        'gan' => 'nam'
                    )
                )
            )
        ), $url->getQueryParams());
    }

    public function testGenerator()
    {
        $app = new \Exedra\Application(__DIR__);

        $app->provider->add(\Exedra\Support\Provider\Framework::class);

        $app->map['foo']->get('/fox')->execute(function(){});

        $generator = new \Exedra\Url\UrlGenerator($app->map, null, 'http://localhost');

        $this->assertEquals($generator->to('foobar'), 'http://localhost/foobar');
    }
}