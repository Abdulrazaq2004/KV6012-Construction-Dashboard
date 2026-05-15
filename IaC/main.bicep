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
resource publicIPAddress 'Microsoft.Network/publicIPAddresses@2023-04-01' = {
  name: '${vmName}-ip'
  location: location
  properties: {
    publicIPAllocationMethod: 'Dynamic'
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
