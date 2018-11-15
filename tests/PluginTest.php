<?php
/**
 *  Copyright MediaCT. All rights reserved.
 *  https://www.mediact.nl
 */

namespace Mediact\ComposerUnclogPlugin\Tests;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Mediact\ComposerUnclogPlugin\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Mediact\ComposerUnclogPlugin\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * @return void
     *
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $subject = new Plugin();
        $this->assertInstanceOf(Plugin::class, $subject);
    }

    /**
     * @return void
     *
     * @covers ::getSubscribedEvents
     */
    public function testGetSubscribedEvents(): void
    {
        $subject = new Plugin();

        $this->assertEquals(
            [
            PluginEvents::COMMAND => [
                ['onCommand', 0]
            ]
            ],
            $subject::getSubscribedEvents()
        );
    }

    /**
     * @return void
     *
     * @covers ::onCommand
     * @covers ::activate
     */
    public function testOnCommand(): void
    {
        $composerMock = $this->createMock(Composer::class);
        $configMock   = $this->createMock(Config::class);
        $composerMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($configMock);

        $configMock->expects($this->once())
            ->method('has')
            ->with(Plugin::ADD_ALLOWED_TYPES_CONFIG)
            ->willReturn(true);

        $configMock->expects($this->once())
            ->method('get')
            ->with(Plugin::ADD_ALLOWED_TYPES_CONFIG)
            ->willReturn(['git']);

        $ioMock  = $this->createMock(IOInterface::class);
        $subject = new Plugin();
        $subject->activate(
            $composerMock,
            $ioMock
        );

        $commandEventMock = $this->createMock(CommandEvent::class);
        $commandEventMock->expects($this->once())
            ->method('getCommandName')
            ->willReturn('validate');

        $configMock->expects($this->once())
            ->method('getRepositories')
            ->willReturn(
                [
                    [
                        'type' => 'vcs',
                        'url' => 'git@somewhere.com/some-vendor/some-package'
                    ]
                ]
            );

        $packageMock = $this->createMock(Package::class);
        $composerMock->expects($this->once())
            ->method('getPackage')
            ->willReturn($packageMock);

        $requireMock = $this->createMock(Link::class);
        $packageMock->expects($this->once())
            ->method('getRequires')
            ->willReturn(
                [
                    'some-package' => $requireMock
                ]
            );

        $requireMock->expects($this->once())
            ->method('getConstraint')
            ->willReturn('dev-test/feature');

        $ioMock->expects($this->once())
            ->method('write');

        $subject->onCommand($commandEventMock);
    }
}
