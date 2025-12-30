---
id: cap-sup-004
name: IT-drift & Övervakning
layer: verksamhetsstod
area: IT
level: 2
type: stodformaga
description: Säkerställa stabil, säker och effektiv drift av IT-infrastruktur och applikationer.
owner: IT-driftchef
status: aktiv
maturity: 4
criticality: 5
risk_level: 2
kpis:
  - name: System Uptime
    target: ≥ 99.9%
  - name: Mean Time to Recovery (MTTR)
    target: "< 30 minuter"
  - name: Incident Resolution (P1)
    target: "< 1 timme"
tags: [it, drift, infrastruktur, devops]
updated: 2025-12-30
dependencies:
  - cap-it-003
---

# IT-drift & Övervakning

24/7 drift av IT-system med fokus på tillgänglighet, prestanda och säkerhet.

## Tjänsteområden

### Infrastructure Operations
- Serverdrift (on-prem + cloud)
- Nätverk och säkerhet
- Databaser och storage
- Backup och disaster recovery

### Application Management
- Applikationsövervakning (APM)
- Performance tuning
- Release management
- Incident & problem management

### Security Operations
- SIEM och logganalys
- Säkerhetspatchning
- Vulnerability management
- SOC (Security Operations Center)

## Driftmodell

Vi kör en **hybrid cloud**-modell:
- Kritiska system: On-premise datacenter (Tier 3)
- Skalerbara tjänster: Azure & AWS
- SaaS: Managed services där möjligt

### Automation
- 80% av rutinincidenter hanteras automatiskt
- Infrastructure as Code (Terraform, Ansible)
- Self-healing services

## Prestanda Q4 2024

| KPI | Target | Actual | Status |
|-----|--------|--------|--------|
| Uptime | 99.9% | 99.95% | ✅ |
| MTTR | < 30 min | 22 min | ✅ |
| P1 Resolution | < 1h | 48 min | ✅ |

## Kontinuerlig förbättring

Varje incident resulterar i en **blameless postmortem**. Vi använder learnings för att förbättra automation och monitoring.

> "Good operations is not about preventing problems, it's about recovering quickly" - DevOps Handbook
