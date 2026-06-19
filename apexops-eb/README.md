# ApexOps — Full Deployment Guide

Cloud, AI & Automation consulting platform.
Built with PHP + PostgreSQL, deployed on AWS Elastic Beanstalk via CodePipeline.

---

## Stack

| Layer | Tech |
|---|---|
| Frontend | HTML, CSS, JS (responsive) |
| Backend | PHP 8.1 |
| Database | PostgreSQL 15 (RDS) |
| Hosting | Elastic Beanstalk (PHP + Nginx) |
| CI/CD | GitHub → CodePipeline → CodeBuild → EB |
| Region | af-south-1 (Cape Town) |

---

## Step 1 — Push to GitHub

```bash
git init
git add .
git commit -m "Initial ApexOps deployment"
git branch -M main
git remote add origin https://github.com/YOUR_USERNAME/apexops.git
git push -u origin main
```

---

## Step 2 — Create VPC (AWS Console)

1. Go to **VPC → Create VPC**
2. Select **VPC and more**
3. Name: `apexops-vpc`
4. IPv4 CIDR: `10.0.0.0/16`
5. Availability zones: 2
6. Public subnets: 2
7. Private subnets: 2
8. NAT Gateway: None (for lab)
9. Click **Create VPC**

---

## Step 3 — Create RDS PostgreSQL

1. Go to **RDS → Create database**
2. Engine: **PostgreSQL 15**
3. Template: **Free tier**
4. DB instance identifier: `apexops-db`
5. Master username: `apexops_admin`
6. Master password: `ApexOps_DB_2026!`
7. DB name: `apexopsdb`
8. VPC: select `apexops-vpc`
9. Public access: **Yes** (for lab)
10. Security group: create new → allow port 5432
11. Click **Create database**

Once available, note the **endpoint** — you will need it for Elastic Beanstalk environment variables.

### Seed the database

Connect to RDS and run schema.sql:

```bash
psql -h YOUR_RDS_ENDPOINT -U apexops_admin -d apexopsdb -f schema.sql
```

---

## Step 4 — Create Elastic Beanstalk Environment

1. Go to **Elastic Beanstalk → Create application**
2. Application name: `apexops`
3. Environment name: `apexops-prod`
4. Platform: **PHP 8.1**
5. Application code: **Sample application** (we will deploy via CodePipeline)
6. Preset: **Single instance** (free tier)

### Set environment variables

In your EB environment → **Configuration → Software → Environment properties**:

| Key | Value |
|---|---|
| `RDS_HOSTNAME` | your RDS endpoint |
| `RDS_PORT` | `5432` |
| `RDS_DB_NAME` | `apexopsdb` |
| `RDS_USERNAME` | `apexops_admin` |
| `RDS_PASSWORD` | `ApexOps_DB_2026!` |

---

## Step 5 — Create CodePipeline

1. Go to **CodePipeline → Create pipeline**
2. Pipeline name: `apexops-pipeline`
3. **Source stage**: GitHub (connect your repo, select `main` branch)
4. **Build stage**: AWS CodeBuild
   - Create project: `apexops-build`
   - Runtime: PHP 8.1
   - Buildspec: use `buildspec.yml` in repo
5. **Deploy stage**: Elastic Beanstalk
   - Application: `apexops`
   - Environment: `apexops-prod`
6. Click **Create pipeline**

Every push to `main` will now automatically deploy.

---

## Step 6 — Enable HTTPS

Elastic Beanstalk provides a free domain:
`apexops-prod.eba-xxxxxxxx.af-south-1.elasticbeanstalk.com`

To enable HTTPS on this domain:

1. Go to **AWS Certificate Manager → Request certificate**
2. Request a **public certificate**
3. Domain: `*.elasticbeanstalk.com` won't work — instead use your EB domain exactly
4. Validation: DNS validation
5. Once issued, go to **EB → Configuration → Load balancer → Add listener**
6. Port 443, protocol HTTPS, attach the certificate

> Note: HTTPS on the EB subdomain requires upgrading from single instance to load-balanced.
> For a lab, HTTP on the EB domain is acceptable.

---

## Demo credentials

| Field | Value |
|---|---|
| URL | `http://YOUR_EB_DOMAIN` |
| Portal | `http://YOUR_EB_DOMAIN/login.php` |
| Email | `admin@apexops.io` |
| Password | `Admin@123` |

---

## Project structure

```
apexops/
├── index.html            ← public website
├── login.php             ← portal login
├── dashboard.php         ← portal dashboard
├── incidents.php         ← incident tracker
├── estimator.php         ← cloud cost estimator
├── logout.php            ← session end
├── config.php            ← DB connection (reads EB env vars)
├── schema.sql            ← PostgreSQL tables + seed data
├── buildspec.yml         ← CodeBuild instructions
├── .ebextensions/
│   ├── 01_php.config     ← PHP settings for EB
│   └── 02_packages.config← installs php-pgsql
└── .gitignore
```
