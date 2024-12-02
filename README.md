# IBA Serial Port Communication Library

A professional, cross-platform PHP library for serial port communication, specifically optimized for GSM modem control and AT commands. This robust library provides a reliable interface for serial communication across Windows, Linux, and OSX platforms.

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.3-blue.svg)](https://www.php.org)

## 🚀 Features

- **Cross-Platform Compatibility**
  - Windows COM ports
  - Linux serial devices (/dev/ttyUSB*, /dev/ttyS*)
  - OSX serial devices (/dev/tty.*)

- **Robust Communication**
  - Reliable data transmission and reception
  - Configurable baud rates from 110 to 921600
  - Automatic port cleanup on script termination
  - Non-blocking I/O operations

- **GSM Modem Support**
  - Optimized for AT command communication
  - Support for common GSM modem operations
  - SMS text mode configuration

- **Error Handling**
  - Comprehensive error detection
  - Custom SerialException class
  - Detailed error messages

## 📋 Requirements

- PHP 7.4 or higher
- Appropriate port access permissions
- Platform-specific requirements:
  - **Windows**: No additional requirements
  - **Linux**: `stty` utility (usually pre-installed)
  - **OSX**: Command Line Tools

## 💻 Installation

1. Clone the repository:
```bash
git clone https://github.com/terminalDZ/iba-serial.git
```

2. Include in your project:
```php
require_once 'path/to/iba_serial.php';
```

## 🔧 Basic Usage

### Simple AT Command Example

```php
try {
    // Create a new instance
    $serial = new IBA\IBASerial();
    
    // Configure the port
    $serial->deviceSet("COM12");  // Windows example (use /dev/ttyUSB0 for Linux)
    $serial->confBaudRate(9600);
    
    // Open the port
    $serial->deviceOpen();
    
    // Send data
    $serial->sendMessage("AT\r\n");
    
    // Read response
    $response = $serial->readPort();
    echo "Response: " . $response . "\n";
    
    // Close the port
    $serial->deviceClose();
    
} catch (IBA\SerialException $e) {
    echo "Error: " . $e->getMessage();
}
```

### GSM Modem Configuration Example

```php
try {
    $serial = new IBA\IBASerial();
    
    // Basic setup
    $serial->deviceSet("COM12");
    $serial->confBaudRate(9600);
    $serial->deviceOpen();
    
    // Initialize modem
    $serial->sendMessage("AT\r\n");
    sleep(1);
    
    // Set SMS text mode
    $serial->sendMessage("AT+CMGF=1\r\n");
    sleep(1);
    
    // Read responses
    $response = $serial->readPort();
    echo $response;
    
    $serial->deviceClose();
    
} catch (IBA\SerialException $e) {
    echo "Error: " . $e->getMessage();
}
```

## 📡 Supported Baud Rates

The library supports a wide range of standard baud rates:
- **Low Speed**: 110, 150, 300, 600
- **Standard**: 1200, 2400, 4800, 9600
- **High Speed**: 19200, 38400, 57600, 115200
- **Ultra High Speed**: 230400, 460800, 500000, 576000, 921600

## 🛠️ Advanced Configuration

### Port Configuration
- Default Windows configuration: 8 data bits, no parity, 1 stop bit
- Custom configurations available through OS-specific commands
- Auto-flush behavior can be controlled

### Error Handling Best Practices
```php
try {
    $serial = new IBA\IBASerial();
    // ... operations ...
} catch (IBA\SerialException $e) {
    // Handle device-specific errors
    echo "Serial Error: " . $e->getMessage();
} catch (\Exception $e) {
    // Handle general errors
    echo "General Error: " . $e->getMessage();
} finally {
    // Always close the port
    if (isset($serial)) {
        $serial->deviceClose();
    }
}
```

## 📝 Common AT Commands

| Command | Description | Example Usage |
|---------|-------------|---------------|
| `AT` | Test modem connection | `$serial->sendMessage("AT\r\n");` |
| `AT+CMGF=1` | Set SMS text mode | `$serial->sendMessage("AT+CMGF=1\r\n");` |
| `AT+CMGS` | Send SMS message | `$serial->sendMessage("AT+CMGS=\"+1234567890\"\r\n");` |
| `AT+CSQ` | Check signal quality | `$serial->sendMessage("AT+CSQ\r\n");` |
| `AT+COPS?` | Check network operator | `$serial->sendMessage("AT+COPS?\r\n");` |

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request



## 👤 Author

- **Original Author**: Idriss Boukmouche
- **Version**: 1.0.0
- **GitHub**: [@terminalDZ](https://github.com/terminalDZ)

## 🆘 Support

For issues, questions, or suggestions:
- Create an issue in the GitHub repository
- Contact via email: boukemoucheidriss@gmail.com

