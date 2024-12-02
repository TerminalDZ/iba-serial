<?php
declare(strict_types=1);

namespace IBA;

/**
 * Professional PHP Serial Port Communication Class
 *
 * A robust, cross-platform serial port communication class supporting Windows, Linux, and OSX.
 * Features include configurable baud rates, parity, data bits, stop bits, and flow control.
 *
 * @package     IBA
 * @author      Original: Idriss Boukmouche
 * @version     1.0.0
 */

class SerialException extends \Exception {}

class IBASerial
{
    private const DEVICE_NOTSET = 0;
    private const DEVICE_SET = 1;
    private const DEVICE_OPENED = 2;

    private ?string $device = null;
    private ?string $winDevice = null;
    private $dHandle = null;
    private int $dState = self::DEVICE_NOTSET;
    private string $buffer = "";
    private string $os = "";
    private bool $autoflush = true;

    /**
     * Valid baud rates for all platforms
     */
    private const VALID_BAUD_RATES = [
        110, 150, 300, 600, 1200, 2400, 4800, 9600, 19200, 38400, 
        57600, 115200, 230400, 460800, 500000, 576000, 921600
    ];

    /**
     * Constructor - Detects OS and sets up environment
     * 
     * @throws SerialException If OS is not supported
     */
    public function __construct()
    {
        setlocale(LC_ALL, "en_US");
        $this->detectOS();
        register_shutdown_function([$this, "deviceClose"]);
    }

    /**
     * Detects the operating system and validates requirements
     * 
     * @throws SerialException If OS is not supported or requirements not met
     */
    private function detectOS(): void
    {
        $sysname = php_uname();

        if (substr($sysname, 0, 5) === "Linux") {
            $this->os = "linux";
            if ($this->executeCommand("stty --version") !== 0) {
                throw new SerialException("Linux requires stty command. Please install it.");
            }
        } 
        elseif (substr($sysname, 0, 6) === "Darwin") {
            $this->os = "osx";
        } 
        elseif (substr($sysname, 0, 7) === "Windows") {
            $this->os = "windows";
        } 
        else {
            throw new SerialException("Unsupported operating system: " . $sysname);
        }
    }

    /**
     * Sets the serial port device
     * 
     * @param string $device Device name (e.g., COM1, /dev/ttyS0, /dev/tty.serial)
     * @throws SerialException If device is invalid or already opened
     * @return bool
     */
    public function deviceSet(string $device): bool
    {
        if ($this->dState === self::DEVICE_OPENED) {
            throw new SerialException("Device is already opened. Close it first.");
        }

        switch ($this->os) {
            case 'linux':
                if (preg_match("/^COM(\d+):?$/i", $device, $matches)) {
                    $device = "/dev/ttyS" . ($matches[1] - 1);
                }
                if ($this->executeCommand("stty -F " . $device) === 0) {
                    $this->device = $device;
                    $this->dState = self::DEVICE_SET;
                    return true;
                }
                break;

            case 'osx':
                if ($this->executeCommand("stty -f " . $device) === 0) {
                    $this->device = $device;
                    $this->dState = self::DEVICE_SET;
                    return true;
                }
                break;

            case 'windows':
                if (preg_match("/^COM(\d+):?$/i", $device, $matches)) {
                    $this->winDevice = "COM" . $matches[1];
                    $this->device = "\\\\.\\" . $this->winDevice;
                    $this->dState = self::DEVICE_SET;
                    return true;
                }
                break;
        }

        throw new SerialException("Invalid device: " . $device);
    }

    /**
     * Opens the device for reading and writing
     * 
     * @param string $mode Opening mode (default: "r+b")
     * @throws SerialException If device cannot be opened
     * @return bool
     */
    public function deviceOpen(string $mode = "r+b"): bool
    {
        if ($this->dState === self::DEVICE_OPENED) {
            return true;
        }

        if ($this->dState === self::DEVICE_NOTSET) {
            throw new SerialException("Device must be set before opening");
        }

        if (!preg_match("/^[raw]\+?b?$/", $mode)) {
            throw new SerialException("Invalid mode: " . $mode);
        }

        $this->dHandle = @fopen($this->device, $mode);
        if ($this->dHandle === false) {
            throw new SerialException("Failed to open device: " . $this->device);
        }

        stream_set_blocking($this->dHandle, false);
        $this->dState = self::DEVICE_OPENED;
        return true;
    }

    /**
     * Configures the baud rate
     * 
     * @param int $rate Baud rate
     * @throws SerialException If rate is invalid or device not ready
     * @return bool
     */
    public function confBaudRate(int $rate): bool
    {
        if ($this->dState !== self::DEVICE_SET) {
            throw new SerialException("Device must be set before configuring baud rate");
        }

        if (!in_array($rate, self::VALID_BAUD_RATES)) {
            throw new SerialException("Invalid baud rate: " . $rate);
        }

        switch ($this->os) {
            case 'linux':
                $result = $this->executeCommand("stty -F " . $this->device . " " . $rate);
                break;
            case 'osx':
                $result = $this->executeCommand("stty -f " . $this->device . " " . $rate);
                break;
            case 'windows':
                $result = $this->executeCommand("mode " . $this->winDevice . " BAUD=" . $rate . " PARITY=N DATA=8 STOP=1");
                break;
            default:
                throw new SerialException("Unsupported OS");
        }

        return $result === 0;
    }

    /**
     * Sends data to the serial port
     * 
     * @param string $data Data to send
     * @param float $waitForReply Wait time in seconds (default: 0.1)
     * @throws SerialException If write fails
     * @return bool
     */
    public function sendMessage(string $data, float $waitForReply = 0.1): bool
    {
        if ($this->dState !== self::DEVICE_OPENED) {
            throw new SerialException("Device must be opened before sending data");
        }

        $this->buffer .= $data;

        if ($this->autoflush) {
            if (!$this->serialFlush()) {
                throw new SerialException("Failed to write to device");
            }
        }

        if ($waitForReply > 0) {
            usleep((int)($waitForReply * 1000000));
        }

        return true;
    }

    /**
     * Reads data from the serial port
     * 
     * @param int $count Number of characters to read (0 for all available)
     * @throws SerialException If read fails
     * @return string
     */
    public function readPort(int $count = 0): string
    {
        if ($this->dState !== self::DEVICE_OPENED) {
            throw new SerialException("Device must be opened before reading");
        }

        if ($this->os === "windows") {
            return $this->windowsRead($count);
        }

        return $this->unixRead($count);
    }

    /**
     * Reads a line from the serial port
     * 
     * @return string
     * @throws SerialException If read fails
     */
    public function readLine(): string
    {
        $line = '';
        $this->setBlocking(true);

        while (true) {
            $char = $this->readPort(1);
            if ($char === "\r" || $char === "\n") {
                if ($line !== '') {
                    break;
                }
            } else {
                $line .= $char;
            }
        }

        $this->setBlocking(false);
        return $line;
    }

    /**
     * Sets blocking mode for the port
     * 
     * @param bool $mode True for blocking, false for non-blocking
     */
    public function setBlocking(bool $mode): void
    {
        if ($this->dHandle) {
            stream_set_blocking($this->dHandle, $mode);
        }
    }

    /**
     * Closes the device
     * 
     * @throws SerialException If close fails
     * @return bool
     */
    public function deviceClose(): bool
    {
        if ($this->dState !== self::DEVICE_OPENED) {
            return true;
        }

        if (!fclose($this->dHandle)) {
            throw new SerialException("Failed to close device");
        }

        $this->dHandle = null;
        $this->dState = self::DEVICE_SET;
        return true;
    }

    /**
     * Executes a system command
     * 
     * @param string $command Command to execute
     * @param array $output Output lines (optional)
     * @return int Exit code
     */
    private function executeCommand(string $command, array &$output = null): int
    {
        $desc = [
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $proc = proc_open($command, $desc, $pipes);
        if (is_resource($proc)) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $returnValue = proc_close($proc);

            if ($output !== null) {
                $output = [$stdout, $stderr];
            }

            return $returnValue;
        }

        return -1;
    }

    /**
     * Windows-specific read implementation
     */
    private function windowsRead(int $count): string
    {
        $content = "";
        $remaining = $count;

        while ($remaining > 0 || $count === 0) {
            $chunk = fread($this->dHandle, ($remaining > 0) ? min($remaining, 128) : 128);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $content .= $chunk;
            $remaining -= strlen($chunk);
        }

        return $content;
    }

    /**
     * Unix-specific read implementation
     */
    private function unixRead(int $count): string
    {
        $content = "";
        $remaining = $count;

        while ($remaining > 0 || $count === 0) {
            $chunk = fread($this->dHandle, ($remaining > 0) ? min($remaining, 128) : 128);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $content .= $chunk;
            $remaining -= strlen($chunk);
        }

        return $content;
    }

    /**
     * Flushes the output buffer
     */
    private function serialFlush(): bool
    {
        if ($this->dState !== self::DEVICE_OPENED) {
            return false;
        }

        if (fwrite($this->dHandle, $this->buffer) !== false) {
            $this->buffer = "";
            return true;
        }

        $this->buffer = "";
        return false;
    }
}