---
id: cap-core-003
name: Orderhantering
layer: karnprocesser
area: Order-to-Cash
level: 2
type: verksamhetsformaga
description: Hantera kundorder från mottagande till leverans och fakturering.
owner: Logistikchef
status: aktiv
maturity: 4
criticality: 5
risk_level: 2
kpis:
  - name: Order Accuracy
    target: ≥ 99.5%
  - name: On-Time Delivery
    target: ≥ 95%
  - name: Order Processing Time
    target: "< 24 timmar"
tags: [order, logistik, leverans]
updated: 2025-12-30
dependencies:
  - cap-core-001
---

# Orderhantering

End-to-end hantering av kundorder - hjärtat i vår operativa verksamhet.

## Orderflöde

```
Order → Validering → Plockning → Packning → Leverans → Fakturering
```

### Kritiska steg

1. **Ordervalidering** - Automatisk kontroll av lagerstatus, kreditgränser
2. **Intelligent routing** - Optimering av plockrutt i lager
3. **Kvalitetskontroll** - 100% scanning innan leverans
4. **Spårning** - Realtidsuppdatering till kund

## Automationsgrad

- 85% av standardorder hanteras helt automatiskt
- AI-baserad efterfrågeprognostisering
- Robotiserad plockning för höglöpande artiklar

## Prestandamått

Senaste kvartalet:
- 99.7% ordernoggrannhet ✅
- 96.2% i tid-leverans ✅
- 18 timmar genomsnittlig handläggningstid ✅

**Nästa steg:** Integrera med kundernas egna system (EDI/API) för friktionsfri ordering.
