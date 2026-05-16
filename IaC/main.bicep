/*
 * main.bicep
 * Infrastructure as Code template for the KV6012 Construction Dashboard
 * This file defines all Azure resources needed to deploy the solution.
 * Running this template replicates the entire infrastructure automatically,
 * without needing to manually click through the Azure portal.
 *
 * Resources defined:
 * - Virtual Network and Subnet
 * - Network Security Group (firewall rules)
 * - Public IP Address
 * - Network Interface
 * - Virtual Machine (Ubuntu 24.04, Apache, PHP, MySQL)
 */

// These parameters allow the template to be reused for different deployments
// For example, you could deploy a test environment and a production environment
// using the same template with different parameter values
@description('Name of the virtual machine')
param vmName string = 'KV6012-VM'

@description('Azure region to deploy resources to')
param location string = 'switzerlandnorth'

@description('Admin username for the VM')
param adminUsername string = 'abdulrazaq'

@description('Admin password for the VM')
@secure()
param adminPassword string

@description('Size of the virtual machine')
param vmSize string = 'Standard_B2ats_v2'

// Virtual Network - provides private network for the VM
resource virtualNetwork 'Microsoft.Network/virtualNetworks@2023-04-01' = {
  name: '${vmName}-vnet'
  location: location
  properties: {
    addressSpace: {
      addressPrefixes: ['10.1.0.0/16']
    }
    subnets: [
      {
        name: 'default'
        properties: {
          addressPrefix: '10.1.0.0/24'
        }
      }
    ]
  }
}

// Network Security Group - acts as a firewall controlling inbound traffic
// Only ports 22 (SSH), 80 (HTTP) and 443 (HTTPS) are allowed in
resource networkSecurityGroup 'Microsoft.Network/networkSecurityGroups@2023-04-01' = {
  name: '${vmName}-nsg'
  location: location
  properties: {
    securityRules: [
      {
        name: 'Allow-SSH'
        properties: {
          priority: 1000
          protocol: 'Tcp'
          access: 'Allow'
          direction: 'Inbound'
          sourceAddressPrefix: '*'
          sourcePortRange: '*'
          destinationAddressPrefix: '*'
          destinationPortRange: '22'
        }
      }
      {
        name: 'Allow-HTTP'
        properties: {
          priority: 1001
          protocol: 'Tcp'
          access: 'Allow'
          direction: 'Inbound'
          sourceAddressPrefix: '*'
          sourcePortRange: '*'
          destinationAddressPrefix: '*'
          destinationPortRange: '80'
        }
      }
      {
        name: 'Allow-HTTPS'
        properties: {
          priority: 1002
          protocol: 'Tcp'
          access: 'Allow'
          direction: 'Inbound'
          sourceAddressPrefix: '*'
          sourcePortRange: '*'
          destinationAddressPrefix: '*'
          destinationPortRange: '443'
        }
      }
    ]
  }
}

// Public IP Address - allows the VM to be accessible from the internet
// Using Standard SKU with Static allocation as Basic SKU limit is reached in this region
resource publicIPAddress 'Microsoft.Network/publicIPAddresses@2023-04-01' = {
  name: '${vmName}-ip'
  location: location
  sku: {
    name: 'Standard'
  }
  properties: {
    publicIPAllocationMethod: 'Static'
  }
}

// Network Interface - connects the VM to the network
resource networkInterface 'Microsoft.Network/networkInterfaces@2023-04-01' = {
  name: '${vmName}-nic'
  location: location
  properties: {
    ipConfigurations: [
      {
        name: 'ipconfig1'
        properties: {
          subnet: {
            id: virtualNetwork.properties.subnets[0].id
          }
          publicIPAddress: {
            id: publicIPAddress.id
          }
        }
      }
    ]
    networkSecurityGroup: {
      id: networkSecurityGroup.id
    }
  }
}

// Virtual Machine - Ubuntu 24.04 server running Apache, PHP and MySQL
resource virtualMachine 'Microsoft.Compute/virtualMachines@2023-03-01' = {
  name: vmName
  location: location
  properties: {
    hardwareProfile: {
      vmSize: vmSize
    }
    osProfile: {
      computerName: vmName
      adminUsername: adminUsername
      adminPassword: adminPassword
      linuxConfiguration: {
        disablePasswordAuthentication: false
      }
    }
    storageProfile: {
      imageReference: {
        publisher: 'canonical'
        offer: 'ubuntu-24_04-lts'
        sku: 'server'
        version: 'latest'
      }
      osDisk: {
        createOption: 'FromImage'
        managedDisk: {
          storageAccountType: 'Premium_LRS'
        }
        deleteOption: 'Delete'
      }
    }
    networkProfile: {
      networkInterfaces: [
        {
          id: networkInterface.id
          properties: {
            deleteOption: 'Delete'
          }
        }
      ]
    }
    securityProfile: {
      uefiSettings: {
        secureBootEnabled: true
        vTpmEnabled: true
      }
      securityType: 'TrustedLaunch'
    }
  }
}

// Custom Script Extension - runs automatically after the VM is created
// Installs Apache, PHP, MySQL, sets up security and deploys the website from GitHub

// $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$
// THIS EXTENSION WAS ENHANCED USING AI 😱😱😱😱😱😱😱😱😱(IT IS NOT REQUIRED IN THE BRIF BUT IT IS NICE TO HAVE) 
// $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$
resource vmExtension 'Microsoft.Compute/virtualMachines/extensions@2023-03-01' = {
  name: 'setup-webserver'
  parent: virtualMachine
  location: location
  properties: {
    publisher: 'Microsoft.Azure.Extensions'
    type: 'CustomScript'
    typeHandlerVersion: '2.1'
    autoUpgradeMinorVersion: true
    settings: {
      script: base64('''#!/bin/bash

# Update the system
apt-get update -y
apt-get upgrade -y

# Install Apache, PHP and MySQL
apt-get install -y apache2 php libapache2-mod-php php-mysqli php-curl unzip mysql-server git

# Enable and start Apache
systemctl enable apache2
systemctl start apache2

# Enable and start MySQL
systemctl enable mysql
systemctl start mysql

# Set up UFW firewall
ufw allow 22
ufw allow 80
ufw allow 443
ufw --force enable

# Hide Apache version
sed -i 's/ServerTokens OS/ServerTokens Prod/' /etc/apache2/conf-available/security.conf
sed -i 's/ServerSignature On/ServerSignature Off/' /etc/apache2/conf-available/security.conf

# Add security headers
cat > /etc/apache2/conf-available/security-headers.conf << 'EOF'
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
EOF

# Enable headers module and security headers config
a2enmod headers
a2enconf security-headers
a2enconf security

# Clone the website code from GitHub
git clone https://github.com/Abdulrazaq2004/KV6012-Construction-Dashboard.git /var/www/html/construction

# Set correct permissions
chown -R www-data:www-data /var/www/html/construction
chmod -R 755 /var/www/html/construction
chmod 777 /var/www/html/construction/images

# Set up the database
mysql -e "CREATE DATABASE construction_projects;"
mysql -e "CREATE USER 'webuser'@'localhost' IDENTIFIED BY 'WebPass123!';"
mysql -e "GRANT ALL PRIVILEGES ON construction_projects.* TO 'webuser'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Create database tables and insert data
mysql construction_projects << 'SQLEOF'
CREATE TABLE Projects (
    Project_id INT PRIMARY KEY,
    Project_Name VARCHAR(255),
    Description TEXT,
    Manager VARCHAR(255),
    Location VARCHAR(255),
    Geolocation VARCHAR(255)
);

CREATE TABLE Resources (
    Resource_id INT PRIMARY KEY,
    Resource_Type VARCHAR(255),
    Conditions_of_use TEXT
);

CREATE TABLE Project_Resources (
    Project_id INT,
    Resource_id INT,
    FOREIGN KEY (Project_id) REFERENCES Projects(Project_id),
    FOREIGN KEY (Resource_id) REFERENCES Resources(Resource_id)
);

INSERT INTO Resources VALUES
(1, 'Crane', 'Do not use in high wind'),
(2, 'Drill', 'Do not use in heavy rain'),
(3, 'Dumper Truck', 'Do not use in heavy rain. Has CO2 emissions so dont use if air quality CO, PM10, PM2.5 or NO2 readings are moderate are poorer.'),
(4, 'Digger', 'Do not use in heavy rain. Has CO2 emissions so dont use if air quality CO, PM10, PM2.5 or NO2 readings are moderate are poorer.'),
(5, 'Loader', 'Do not use in heavy rain. Has CO2 emissions so dont use if air quality CO, PM10, PM2.5 or NO2 readings are moderate are poorer.'),
(6, 'Concrete mixer', 'Do not use in heavy rain');

INSERT INTO Projects VALUES
(1, 'NESST', 'A new university building with lab spaces, meeting rooms, breakout areas, kitchen areas and WC facilities.', 'Chelsea Dawson', 'Northumbria University, Ellison Terrace, Newcastle upon Tyne, NE1 8ST', '54.976414676146824, -1.6066366875533187'),
(2, 'CHASE', 'A new university building with lab spaces, meeting rooms, breakout areas, kitchen areas and WC facilities.', 'Peter Duncan', 'Northumbria University, Ellison Terrace, Newcastle upon Tyne, NE1 8ST', '54.97919158255862, -1.6064863942439456'),
(3, 'HMRC', 'An office space for a public sector client to include gym space, staff rooms with kitchen areas, toilet facilities, meeting rooms and breakout areas.', 'Dan Smith', 'New Bridge Street, Newcastle upon Tyne, NE1 2SW', '54.97419179801806, -1.6113036886189427'),
(4, 'St James Park', 'An extension to the existing football stadium to include a clubhouse for coaching non-professional players and hosting events. To include a small field, an exhibition room, toilet facilities and a kitchen.', 'Chelsea Dawson', 'Newcastle United Football Co Ltd, St. James Park, Strawberry Place, Newcastle upon Tyne, NE1 4ST', '54.97470900180268, -1.6204767255123336');

INSERT INTO Project_Resources VALUES
(1,1),(1,2),(1,3),(1,4),(1,5),
(2,1),(2,2),(2,3),(2,4),(2,5),
(3,1),(3,4),(3,5),(3,6),
(4,1),(4,3),(4,5),(4,6);
SQLEOF

# Restart Apache to apply all changes
systemctl restart apache2

echo "Setup complete!"
''')
    }
  }
}
