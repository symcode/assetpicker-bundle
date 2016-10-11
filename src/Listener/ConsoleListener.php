<?php
/**
 * See class comment
 *
 * PHP Version 5
 *
 * @category Netresearch
 * @package  Netresearch\AssetPickerBundle\Listener
 * @author   Christian Opitz <christian.opitz@netresearch.de>
 * @license  http://www.netresearch.de Netresearch Copyright
 * @link     http://www.netresearch.de
 */

namespace Netresearch\AssetPickerBundle\Listener;
use Netresearch\AssetPicker;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Finder\Finder;

/**
 * Listener for console command events
 *
 * @category Netresearch
 * @package  Netresearch\AssetPickerBundle\Listener
 * @author   Christian Opitz <christian.opitz@netresearch.de>
 * @license  http://www.netresearch.de Netresearch Copyright
 * @link     http://www.netresearch.de
 */
class ConsoleListener
{
    /**
     * Listen for termination of assets:install command to add assetpicker sources
     *
     * @param ConsoleEvent $event
     *
     * @return void
     */
    public function onCommandTerminate(ConsoleEvent $event)
    {
        $command = $event->getCommand();
        if ($command->getName() === 'assets:install') {
            $output = $event->getOutput();
            $input = $event->getInput();
            /* @var \Symfony\Component\Filesystem\Filesystem $filesystem */
            $filesystem = $command->getApplication()->getKernel()->getContainer()->get('filesystem');
            /* @var \Netresearch\AssetPickerBundle\AssetPickerBundle $bundle */
            $bundle = $command->getApplication()->getKernel()->getBundle('AssetPickerBundle');

            $bundlesDir = rtrim($input->getArgument('target'), '/') . '/bundles';
            $filesystem->mkdir($bundlesDir);

            $originDir = realpath(AssetPicker::getDistPath());
            $targetDir = $bundlesDir . '/' . preg_replace('/bundle$/', '', strtolower($bundle->getName()));
            $output->writeln(sprintf('Installing assets for <comment>%s</comment> into <comment>%s</comment>', $bundle->getNamespace(), $targetDir));

            $filesystem->remove($targetDir);

            $symlink = $input->getOption('symlink');
            $relative = $input->getOption('relative');
            // relative implies symlink
            if ($symlink || $relative) {
                if ($out = $this->symlink($filesystem, $relative, $originDir, $bundlesDir, $targetDir)) {
                    $output->writeln($out);
                }
            } else {
                $this->hardCopy($filesystem, $originDir, $targetDir);
            }
        }
    }

    /**
     * Symlink the sources - adapted from \Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand
     *
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem
     * @param boolean                                  $relative
     * @param string                                   $originDir
     * @param string                                   $bundlesDir
     * @param string                                   $targetDir
     *
     * @return string|void
     */
    protected function symlink($filesystem, $relative, $originDir, $bundlesDir, $targetDir)
    {
        if ($relative) {
            $relativeOriginDir = $filesystem->makePathRelative($originDir, realpath($bundlesDir));
        } else {
            $relativeOriginDir = $originDir;
        }
        try {
            $filesystem->symlink($relativeOriginDir, $targetDir);
            if (!file_exists($targetDir)) {
                throw new IOException('Symbolic link is broken');
            }
            return 'The assets were installed using symbolic links.';
        } catch (IOException $e) {
            if (!$relative) {
                $this->hardCopy($filesystem, $originDir, $targetDir);
                return 'It looks like your system doesn\'t support symbolic links, so the assets were installed by copying them.';
            }

            // try again without the relative option
            try {
                $filesystem->symlink($originDir, $targetDir);
                if (!file_exists($targetDir)) {
                    throw new IOException('Symbolic link is broken');
                }
                return 'It looks like your system doesn\'t support relative symbolic links, so the assets were installed by using absolute symbolic links.';
            } catch (IOException $e) {
                $this->hardCopy($filesystem, $originDir, $targetDir);
                return 'It looks like your system doesn\'t support symbolic links, so the assets were installed by copying them.';
            }
        }
    }

    /**
     * Copy the sources - adapted from \Symfony\Bundle\FrameworkBundle\Command\AssetsInstallCommand
     *
     * @param \Symfony\Component\Filesystem\Filesystem $filesystem
     * @param string $originDir
     * @param string $targetDir
     */
    private function hardCopy($filesystem, $originDir, $targetDir)
    {
        $filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));
    }
}
