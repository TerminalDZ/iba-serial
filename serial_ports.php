<?php

require_once 'iba_serial.php';

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
    
    // Wait for response
    sleep(1);
    
    // Send data
    $serial->sendMessage("AT+CMGF=1\r\n");
    
   
    
    // Read response
    $response = $serial->readPort();
    echo "Response: " . $response . "\n";
    
    // Close the port
    $serial->deviceClose();
    
} catch (IBA\SerialException $e) {
    echo "Error: " . $e->getMessage();
}