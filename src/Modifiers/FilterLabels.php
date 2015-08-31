<?php
/**
 * League.Url (http://url.thephpleague.com)
 *
 * @package   League.uri
 * @author    Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright 2013-2015 Ignace Nyamagana Butera
 * @license   https://github.com/thephpleague/uri/blob/master/LICENSE (MIT License)
 * @version   4.0.0
 * @link      https://github.com/thephpleague/uri/
 */
namespace League\Uri\Modifiers;

use League\Uri\Components\Host;
use League\Uri\Modifiers\Filters\Flag;
use League\Uri\Modifiers\Filters\ForCallable;

/**
 * Filter the host component labels
 *
 * @package League.uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   4.0.0
 */
class FilterLabels extends AbstractHostModifier
{
    use ForCallable;
    use Flag;

    /**
     * New instance
     *
     * @param callable $callable
     * @param int      $flag
     */
    public function __construct(callable $callable, $flag = Host::FILTER_USE_VALUE)
    {
        $this->callable = $callable;
        $this->flag = $this->filterFlag($flag);
    }

    /**
     * {@inheritdoc}
     */
    protected function modify($str)
    {
        return (string) (new Host($str))->filter($this->callable, $this->flag);
    }
}