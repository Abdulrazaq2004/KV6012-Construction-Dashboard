# KV6012 Cloud Computing - Construction Project Dashboard

## Student Information
- **Module:** KV6012 Cloud Computing
- **University:** Northumbria University
- **Academic Year:** 2025-26 Semester 2

## Live Solution URL
```
http://20.208.137.109/construction/
```

## GitHub Repository
```
https://github.com/Abdulrazaq2004/KV6012-Construction-Dashboard
```

---

## Project Overview

A cloud-hosted construction project dashboard built on Microsoft Azure. The dashboard displays live weather, air quality and map data for construction project sites in Newcastle upon Tyne, helping site managers make decisions about whether to use certain equipment based on current conditions.

---

## File Structure

```
construction/
├── index.php               # Homepage — lists all 4 construction projects
├── project.php             # Project detail page — map, weather, air quality, forecasts
├── about.php               # About page — references and credits
├── css/
│   └── style.css           # Main stylesheet
├── includes/
│   ├── db.php              # Database connection
│   └── weather.php         # OpenWeather API functions
├── images/
│   ├── me1.jpeg            # Team photos
│   ├── me2.jpeg
│   └── me3.jpeg
├── IaC/
│   └── main.bicep          # Infrastructure as Code — Bicep template
└── README.md               # This file
```

---

## Cloud Architecture

- **Platform:** Microsoft Azure
- **Region:** Switzerland North
- **VM Size:** Standard_B2ats_v2 (2 vCPUs, 1GB RAM)
- **OS:** Ubuntu Server 24.04 LTS
- **Web Server:** Apache 2.4
- **Language:** PHP 8.3
- **Database:** MySQL 8.0

---

## Security Configuration

- Azure Network Security Group (ports 22, 80, 443)
- UFW Linux firewall (ports 22, 80, 443)
- Apache security headers (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy)
- Apache and PHP version numbers hidden
- SQL injection prevention using PDO prepared statements
- XSS prevention using htmlspecialchars()
- Role-based access control (tutor accounts: Reader access)
- Resource lock applied to VM

---

## APIs Used

- **OpenWeather API** — current weather, air quality, 5-day forecast, air quality forecast
- **OpenStreetMap + Leaflet.js v1.9.4** — interactive map with project location marker

---

## Infrastructure as Code (IaC)

The `IaC/main.bicep` file is a Bicep template that fully automates the deployment of the entire solution including:

- Virtual Network and Subnet
- Network Security Group with firewall rules
- Public IP Address
- Network Interface
- Virtual Machine (Ubuntu 24.04)
- Custom Script Extension that automatically:
  - Installs Apache, PHP, MySQL
  - Configures UFW firewall and security headers
  - Clones website code from GitHub
  - Creates database tables and inserts all project data

### To deploy using Azure CLI:

```powershell
# Login to Azure
az login

# Create a resource group
az group create --name KV6012-Test --location switzerlandnorth

# Deploy the template
az deployment group create --resource-group KV6012-Test --template-file IaC/main.bicep
```

Enter a password when prompted. The full website will be deployed automatically.

---

## Database Structure

Three tables linked with foreign keys:

- **Projects** — 4 construction projects (NESST, CHASE, HMRC, St James Park)
- **Resources** — 6 equipment types (Crane, Drill, Dumper Truck, Digger, Loader, Concrete Mixer)
- **Project_Resources** — links projects to their equipment

---

## Sustainability

- Website carbon rating: **A+** (tested on [websitecarbon.com](https://www.websitecarbon.com/website/20-208-137-109-construction/))
- No CSS frameworks used — hand-written minimal stylesheet
- No unnecessary images or media files
- VM stopped when not in use to reduce idle compute consumption

---

## Deadlines

- **Part 1 (Practical):** Monday 18th May 16:00 GMT 2026
- **Part 2 (Report):** Thursday 21st May 16:00 GMT 2026
