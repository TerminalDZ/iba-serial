<?php

require_once 'iba_serial.php';

try {
    // Create a new instance
    $serial = new IBA\IBASerial();
    
    // Get available ports
    $available_ports = $serial->getAvailablePorts();
    
    if (empty($available_ports)) {
        throw new IBA\SerialException("No COM ports found. Please connect a device.");
    }
    
    echo "Available COM ports: " . implode(", ", $available_ports) . "\n";
    
    // Use the first available port
    $port = $available_ports[0];
    echo "Using port: " . $port . "\n";
    
    // Configure the port
    $serial->deviceSet($port);
    $serial->confBaudRate(9600);
    
    // Open the port
    $serial->deviceOpen();
    
    // Send AT command
    echo "Sending AT command...\n";
    $serial->sendMessage("AT\r\n");
    
    // Wait for response
    sleep(1);
    
    // Send SMS text mode command
    echo "Setting SMS text mode...\n";
    $serial->sendMessage("AT+CMGF=1\r\n");
    
    // Wait for response
    sleep(1);
    
    // Read response
    $response = $serial->readPort();
    echo "Response: " . $response . "\n";
    
    // Close the port
    $serial->deviceClose();
    
} catch (IBA\SerialException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "General Error: " . $e->getMessage() . "\n";
}