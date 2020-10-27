<?php
/**
 *  Copyright MediaCT. All rights reserved.
 *  https://www.mediact.nl
 */

namespace Mediact\ComposerUnclogPlugin;

use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    const ADD_ALLOWED_TYPES_CONFIG = 'add-allowed-repositories';

    const DEFAULT_CONFIGURATION = [
        'allowed-package-types' => ['composer'],
        'hook-commands' => ['validate', 'install', 'update', 'require']
    ];

    /** @var Composer */
    private $composer;

    /** @var IOInterface */
    private $io;

    /** @var array */
    private $configuration;

    /** @var Config */
    private $config;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->configuration = static::DEFAULT_CONFIGURATION;
    }

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer    $composer
     * @param IOInterface $io
     *
     * @return void
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->config = $composer->getConfig();
        if ($this->config->has(static::ADD_ALLOWED_TYPES_CONFIG)) {
            $this->configuration['allowed-package-types'] = array_reduce(
                $this->config->get(static::ADD_ALLOWED_TYPES_CONFIG),
                function (array $carry, string $subject) {
                    $carry[] = $subject;
                    return $carry;
                },
                $this->configuration['allowed-package-types']
            );
        }

        $this->composer = $composer;
        $this->io       = $io;
    }

    /**
     * Remove any hooks from Composer
     *
     * This will be called when a plugin is deactivated before being
     * uninstalled, but also before it gets upgraded to a new version
     * so the old one can be deactivated and the new one activated.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {

    }

    /**
     * Prepare the plugin to be uninstalled
     *
     * This will be called after deactivate.
     *
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {

    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     * * The method name to call (priority defaults to 0)
     * * An array composed of the method name to call and the priority
     * * An array of arrays composed of the method names to call and respective
     *   priorities, or 0 if unset
     *
     * For instance:
     *
     * * array('eventName' => 'methodName')
     * * array('eventName' => array('methodName', $priority))
     * * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::COMMAND => [
                ['onCommand', 0]
            ]
        ];
    }

    /**
     * Hooks in on the command event.
     *
     * @param CommandEvent $event
     *
     * @return void
     */
    public function onCommand(CommandEvent $event): void
    {
        if (in_array(
            $event->getCommandName(),
            $this->configuration['hook-commands']
        )) {
            $warningList = [];

            foreach ($this->config->getRepositories() as
                     $repositoryName => $repository) {
                if (!in_array(
                    $repository['type'],
                    $this->configuration['allowed-package-types']
                )) {
                    $warningList[] = sprintf(
                        '- Repository with URL "%s" contains a disallowed '.
                        'package type "%s". It\'s recommended to change this to "%s".',
                        $repository['url'] ?? '',
                        $repository['type'],
                        implode(
                            ', ',
                            $this->configuration['allowed-package-types']
                        )
                    );
                }
            }

            foreach ($this->composer->getPackage()->getRequires() as
                     $requireName => $require) {
                if (preg_match('/dev-/', $require->getConstraint()) > 0) {
                    $warningList[] = sprintf(
                        '- Package "%s" is on a "dev" branch. '.
                        'It\'s recommended to change this to a fixed or dynamic version.',
                        $requireName
                    );
                }
            }

            if (count($warningList) > 0) {
                $this->io->write(
                    array_merge(
                        [
                            'The composer.json file contains warnings:',
                            ''
                        ],
                        $warningList,
                        [
                            '',
                            'Consider taking action.'
                        ]
                    ),
                    true
                );
            }
        }
    }
}
