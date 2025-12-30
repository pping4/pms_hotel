# Product Requirements Document (PRD) - Hotel PMS

## 1. บทนำ (Introduction)
เอกสารฉบับนี้รวบรวมความต้องการของระบบ Hotel PMS (Property Management System) เพื่อใช้ในการพัฒนาระบบบริหารจัดการโรงแรมที่ครบวงจร ตั้งแต่การจองห้องพัก การเช็คอิน-เช็คเอาท์ การจัดการแม่บ้าน ไปจนถึงระบบบัญชีและรายงาน

## 2. วัตถุประสงค์ (Objectives)
1. เพิ่มประสิทธิภาพในการบริหารจัดการห้องพักและการบริการลูกค้า
2. ลดความผิดพลาดในการจองและสถานะห้องพัก
3. รวบรวมข้อมูลการเงินและรายงานสรุปยอดขายได้แบบ Real-time
4. รองรับการทำงานผ่านระบบ Cloud และเข้าใช้งานได้จากทุกที่

## 3. กลุ่มผู้ใช้งาน (User Personas)
*   **Admin**: ผู้ดูแลระบบ สามารถตั้งค่าระบบ กำหนดสิทธิ์ผู้ใช้ และแก้ไขข้อมูล master data
*   **Receptionist**: พนักงานต้อนรับ จัดการ Check-in/Check-out, รับจองห้องพัก, รับชำระเงิน
*   **Housekeeping**: แม่บ้าน ดูสถานะห้องที่ต้องทำความสะอาด และอัปเดตสถานะเมื่อเสร็จงาน
*   **Manager/Owner**: ผู้บริหาร ดู Dashboard และรายงานสรุปผลประกอบการ

## 4. ความต้องการของระบบ (Functional Requirements)

### 4.1 ด้านการจัดการห้องพัก (Room Management)
*   [ ] **Room Status**: แสดงสถานะห้องแบบ Real-time (ว่าง, มีแขกพัก, กำลังทำความสะอาด, ปิดปรับปรุง)
*   [ ] **Check-in/Check-out**: ระบบเช็คอินและเช็คเอาท์ที่รวดเร็ว รองรับการอ่านบัตรประชาชน/พาสปอร์ต (Future phase)
*   [ ] **Room Master**: จัดการประเภทห้อง (Room Types), หมายเลขห้อง, และราคา (Pricing)
*   [ ] **Housekeeping Management**: ระบบมอบหมายงานแม่บ้าน และติดตามสถานะการทำความสะอาด

### 4.2 ด้านการจองและการขาย (Booking & Sales)
*   [ ] **Reservation**: ระบบจองห้องพักล่วงหน้า (Walk-in, Phone, Email)
*   [ ] **Calendar View**: ปฏิทินแสดงการจองห้องพักทั้งหมด
*   [ ] **Price & Promotion**: ตั้งค่าราคาตามช่วงเวลา (Seasonality) และโปรโมชั่นต่างๆ
*   [ ] **Channel Manager Integration**: (Optional/Future Phase) เชื่อมต่อ API กับ OTA (Booking.com, Agoda)
*   [ ] **Booking Engine**: (Optional/Future Phase) หน้าเว็บสำหรับให้ลูกค้าจองห้องพักโดยตรง

### 4.3 ด้านการเงินและบัญชี (Finance & Accounting)
*   [ ] **Billing**: ออกใบแจ้งหนี้ (Invoice), ใบเสร็จรับเงิน (Receipt), ใบกำกับภาษี
*   [ ] **Payment Methods**: รองรับการชำระเงินหลายรูปแบบ (เงินสด, บัตรเครดิต, โอนเงิน) และหลายสกุลเงิน
*   [ ] **Expense Recording**: บันทึกรายจ่ายเบ็ดเตล็ดของโรงแรม
*   [ ] **POS Integration**: (Future Phase) เชื่อมต่อยอดขายจากมินิบาร์หรือห้องอาหารเข้าสู่บิลห้องพัก

### 4.4 ด้านข้อมูลและรายงาน (Data & Reporting)
*   [ ] **Dashboard**: หน้าสรุปภาพรวมห้องว่าง, จำนวนแขกที่เข้าพัก, ยอดขายประจำวัน
*   [ ] **Occupancy Report**: รายงานอัตราการเข้าพัก
*   [ ] **Revenue Report**: รายงานรายได้แยกตามวัน/เดือน/ปี
*   [ ] **Guest History (CRM)**: เก็บประวัติลูกค้า ข้อมูลการติดต่อ และประวัติการเข้าพัก

### 4.5 ระบบความปลอดภัยและสิทธิ์การใช้งาน (Security & Access Control)
*   [ ] **User Authentication**: ระบบ Login แยกตาม User Role
*   [ ] **Audit Log**: (Optional) บันทึกประวัติการทำรายการสำคัญ

## 5. ความต้องการด้านเทคนิค (Non-Functional Requirements)
*   **Platform**: Web Application
*   **Database**: MySQL / MariaDB
*   **Technology Stack**: PHP (Native/Framework), HTML5, CSS3, JavaScript
*   **Infrastructure**: รองรับการ Deploy บน Cloud Server หรือ Local Server (XAMPP)
*   **Data Security**: มีระบบสำรองข้อมูล (Backup) และเข้ารหัสรหัสผ่าน

## 6. แผนการพัฒนา (Phasing)
*   **Phase 1**: Core PMS (Room, Booking, Check-in/out, Basic Report)
*   **Phase 2**: Finance & Housekeeping
*   **Phase 3**: CRM & Advanced Reporting
*   **Phase 4**: Channel Manager & POS Integration
