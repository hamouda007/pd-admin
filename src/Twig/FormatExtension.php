<?php

/**
 * This file is part of the pdAdmin package.
 *
 * @package     pd-admin
 *
 * @license     LICENSE
 * @author      Kerem APAYDIN <kerem@apaydin.me>
 *
 * @link        https://github.com/appaydin/pd-admin
 */

namespace App\Twig;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig Extension.
 *
 * @author Kerem APAYDIN <kerem@apaydin.me>
 */
class FormatExtension extends AbstractExtension
{
    /**
     * Translator.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ParameterBagInterface
     */
    private $bag;

    /**
     * Constructor.
     *
     * @param TranslatorInterface   $translator
     * @param ParameterBagInterface $bag
     */
    public function __construct(TranslatorInterface $translator, ParameterBagInterface $bag)
    {
        $this->translator = $translator;
        $this->bag = $bag;
    }

    /**
     * Create Twig Filter.
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new TwigFilter('timeDiff', [$this, 'timeDiffFilter'], ['needs_environment' => true]),
            new TwigFilter('phoneFormat', [$this, 'phoneFormatFilter']),
            new TwigFilter('basename', [$this, 'baseNameFilter']),
            new TwigFilter('swiftEvent', [$this, 'swiftEventFilter']),
        ];
    }

    /**
     * Create Twig Function.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('parameters', [$this, 'parametersFunction']),
            new TwigFunction('title', [$this, 'titleFunction']),
            new TwigFunction('inArray', [$this, 'inArrayFunction']),
            new TwigFunction('pathInfo', [$this, 'pathInfoFunction']),
        ];
    }

    /**
     * Time Ago.
     *
     * @param Environment $env
     * @param $date
     * @param null   $now
     * @param string $text
     * @param int    $length
     * @param string $domain
     *
     * @return string
     */
    public function timeDiffFilter(Environment $env, $date, $now = null, $text = 'diff.ago', $domain = 'messages', $length = 1)
    {
        $units = [
            'y' => $this->translator->trans('diff.year', [], $domain),
            'm' => $this->translator->trans('diff.month', [], $domain),
            'd' => $this->translator->trans('diff.day', [], $domain),
            'h' => $this->translator->trans('diff.hour', [], $domain),
            'i' => $this->translator->trans('diff.minute', [], $domain),
            's' => $this->translator->trans('diff.second', [], $domain),
        ];

        // Date Time
        $date = twig_date_converter($env, $date);
        $now = twig_date_converter($env, $now);

        // Convert
        $diff = $date->diff($now);
        $format = '';

        $counter = 0;
        foreach ($units as $key => $val) {
            $count = $diff->$key;

            if (0 !== $count) {
                $format .= $count.' '.$val.' ';

                ++$counter;
                if ($counter === $length) {
                    break;
                }
            }
        }

        return ($format) ? $format.$this->translator->trans($text, [], $domain) : '';
    }

    /**
     * Phone Formatter.
     *
     * @param $phone
     *
     * @return string
     */
    public function phoneFormatFilter($phone)
    {
        // Null | Empty | 0
        if (empty($phone) || 0 === $phone) {
            return '';
        }

        return mb_substr($phone, 0, 3).'-'.mb_substr($phone, 3, 3).'-'.mb_substr($phone, 6);
    }

    /**
     * Basename Formatter.
     *
     * @param $path
     *
     * @return string
     */
    public function baseNameFilter($path)
    {
        return basename($path);
    }

    /**
     * SwiftMailer Event Convert.
     *
     * @param $event
     * @param bool $color
     *
     * @return string
     */
    public function swiftEventFilter($event, $color = false)
    {
        $str = '';

        switch ($event) {
            case \Swift_Events_SendEvent::RESULT_SUCCESS:
                $str = $color ? 'success' : $this->translator->trans('RESULT_SUCCESS');
                break;
            case \Swift_Events_SendEvent::RESULT_FAILED:
                $str = $color ? 'danger' : $this->translator->trans('RESULT_FAILED');
                break;
            case \Swift_Events_SendEvent::RESULT_SPOOLED:
                $str = $color ? 'primary' : $this->translator->trans('RESULT_SPOOLED');
                break;
            case \Swift_Events_SendEvent::RESULT_PENDING:
                $str = $color ? 'warning' : $this->translator->trans('RESULT_PENDING');
                break;
            case \Swift_Events_SendEvent::RESULT_TENTATIVE:
                $str = $color ? 'info' : $this->translator->trans('RESULT_TENTATIVE');
                break;
            case -1:
                $str = $color ? 'secondary' : $this->translator->trans('RESULT_DELETED');
                break;
        }

        return $str;
    }

    /**
     * Return Parameters.
     *
     * @param $name
     * @param int $index
     *
     * @return mixed
     */
    public function parametersFunction($name, $index = 0)
    {
        $params = $this->bag->get($name);

        if ('false' === $index) {
            return $params;
        }

        if (\is_array($params)) {
            return $params[$index];
        }

        return $params;
    }

    /**
     * Return Panel Title.
     *
     * @param $title
     * @param bool $parent
     *
     * @return mixed
     */
    public function titleFunction($title, $parent = true)
    {
        if (!$parent) {
            return $title;
        }

        $getTitle = str_replace('&T', $title, $this->bag->get('head_title_pattern'));
        $getTitle = str_replace('&P', $this->bag->get('head_title'), $getTitle);

        return $getTitle;
    }

    /**
     * Checks if a value exists in an array.
     *
     * @param $needle
     * @param array $haystack
     *
     * @return bool
     */
    public function inArrayFunction($needle, array $haystack): bool
    {
        return \in_array(mb_strtolower($needle), $haystack);
    }

    /**
     * Information about a file path.
     *
     * @param string $path
     * @param string $options
     *
     * @return string
     */
    public function pathInfoFunction(string $path, $options = 'extension'): string
    {
        return pathinfo($path)[mb_strtolower($options)];
    }
}
