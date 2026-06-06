const fs = require("fs");
const {
  Document, Packer, Paragraph, TextRun, AlignmentType,
  HeadingLevel, BorderStyle, LevelFormat,
} = require("docx");

// ---- Brand palette (8AM Coffee) ----
const RED = "E82C2A";
const BROWN = "522C25";
const GRAY = "777777";

const FONT = "Times New Roman";
const SZ = 26; // 13pt body (half-points)

// ---- helpers ----
const body = (runs, opts = {}) =>
  new Paragraph({
    alignment: AlignmentType.JUSTIFIED,
    spacing: { after: 140, line: 312 }, // 1.3 line
    children: Array.isArray(runs) ? runs : [new TextRun({ text: runs })],
    ...opts,
  });

const t = (text, o = {}) => new TextRun({ text, font: FONT, size: SZ, ...o });

const bullet = (text) =>
  new Paragraph({
    numbering: { reference: "ui-bullets", level: 0 },
    alignment: AlignmentType.JUSTIFIED,
    spacing: { after: 80, line: 300 },
    children: Array.isArray(text) ? text : [t(text)],
  });

// Figure placeholder: a bordered box (image slot) + caption "Hình x" + Nguồn
let figNo = 0;
function figure(slotLabel, caption, source = "Nhóm thực hiện chụp từ hệ thống 8AM Coffee") {
  figNo += 1;
  const boxBorder = { style: BorderStyle.DASHED, size: 6, color: "BBBBBB", space: 6 };
  return [
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { before: 120, after: 0, line: 360 },
      shading: { type: "clear", fill: "EFEAE4" },
      children: [
        new TextRun({ text: "[ Ảnh chụp màn hình: ", font: FONT, size: 24, italics: true, color: GRAY }),
        new TextRun({ text: slotLabel, font: FONT, size: 24, italics: true, color: GRAY }),
        new TextRun({ text: " ]", font: FONT, size: 24, italics: true, color: GRAY }),
        new TextRun({ break: 1, text: "(chèn hình tại đây)", font: FONT, size: 20, italics: true, color: "AAAAAA" }),
      ],
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { before: 80, after: 20 },
      children: [
        new TextRun({ text: `Hình 2.${figNo}: `, font: FONT, size: 24, bold: true }),
        new TextRun({ text: caption, font: FONT, size: 24, bold: true }),
      ],
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { after: 200 },
      children: [
        new TextRun({ text: "Nguồn: ", font: FONT, size: 22, italics: true }),
        new TextRun({ text: source, font: FONT, size: 22, italics: true }),
      ],
    }),
  ];
}

const h2 = (text) =>
  new Paragraph({ heading: HeadingLevel.HEADING_2, spacing: { before: 240, after: 160 },
    children: [new TextRun({ text, font: FONT, size: 30, bold: true, color: BROWN })] });

const h3 = (num, text) =>
  new Paragraph({ spacing: { before: 200, after: 100 },
    children: [
      new TextRun({ text: `${num}. `, font: FONT, size: 27, bold: true, color: RED }),
      new TextRun({ text, font: FONT, size: 27, bold: true, color: BROWN }),
    ] });

// ================= CONTENT =================
const children = [];

children.push(h2("2.2. Thiết kế giao diện"));

children.push(body([
  t("Thiết kế giao diện hệ thống "),
  t("đặt món và quản lý quán cà phê 8AM Coffee", { bold: true }),
  t(" hướng đến mục tiêu tạo ra trải nghiệm người dùng tối ưu, hiện đại, dễ sử dụng và phù hợp với thói quen gọi món tại quán cũng như mang về. Hệ thống vận hành theo mô hình "),
  t("đặt món tại bàn bằng mã QR", { bold: true }),
  t(": khách hàng quét mã QR dán trên bàn, tự xem thực đơn, xem mô hình 3D của ly nước, chọn món kèm tùy chọn (nóng/lạnh, topping, ghi chú) rồi thanh toán mà không cần cài đặt ứng dụng."),
]));

children.push(bullet([
  t("Giao diện dành cho khách hàng", { bold: true }),
  t(" – nhóm người dùng chính có nhu cầu xem thực đơn, chọn bàn, đặt món, thanh toán và theo dõi trạng thái đơn hàng theo thời gian thực."),
]));
children.push(bullet([
  t("Giao diện dành cho quản trị viên và nhân viên", { bold: true }),
  t(" – để quản lý thực đơn, topping, bàn và mã QR, điều phối đơn tại quầy, quản lý kho nguyên liệu, nhân viên, khách hàng và xem các báo cáo phân tích thông minh."),
]));

children.push(body([
  t("Bản đồ giao diện (UI map) của hệ thống bao gồm các trang chính: "),
  t("Quét QR / Đăng nhập bàn, Thực đơn, Chi tiết món & Xem mô hình 3D, Sơ đồ bàn 3D, Giỏ hàng & Thanh toán, Trạng thái đơn hàng", { bold: true }),
  t(" (phía khách hàng); cùng "),
  t("Đăng nhập có xác thực OTP, Bảng điều khiển, Bảng điều phối đơn, Đặt món tại quầy (POS), Quản lý thực đơn – topping – bàn & QR, Quản lý kho, Nhân viên, Khách hàng, Phân tích AI và Nhật ký bảo mật", { bold: true }),
  t(" (phía quản trị). Các trang được liên kết logic, đảm bảo người dùng di chuyển giữa các chức năng chỉ với vài thao tác đơn giản."),
]));

children.push(...figure(
  "Bản đồ giao diện (UI map) tổng thể của hệ thống 8AM Coffee",
  "Bản đồ giao diện (UI map) của hệ thống 8AM Coffee"));

children.push(body([
  t("Các thành phần giao diện được thiết kế chi tiết như sau:", { bold: true }),
]));

// 1. Header
children.push(h3(1, "Thanh điều hướng (Header)"));
children.push(body([
  t("Luôn xuất hiện ở đầu trang, chứa "),
  t("logo thương hiệu 8AM Coffee", { bold: true }),
  t(" nổi bật, menu điều hướng ngang với các mục chính (Thực đơn, Sơ đồ bàn, Đơn của tôi đối với khách; Bảng điều khiển, Đơn hàng, Thực đơn, Kho, Phân tích, Hệ thống đối với quản trị). Một số mục có dạng dropdown cho các mục con. Trên giao diện khách, header còn hiển thị "),
  t("số bàn hiện tại và nút giỏ hàng", { bold: true }),
  t(". Header dùng tông màu thương hiệu (đỏ #E82C2A, nâu #522C25), font tiêu đề rõ ràng, các nút bấm lớn, dễ thao tác trên cả desktop lẫn mobile."),
]));
children.push(...figure(
  "Thanh điều hướng (header) trên giao diện khách hàng và giao diện quản trị",
  "Thanh điều hướng (Header) của hệ thống"));

// 2. Banner / Hero
children.push(h3(2, "Banner trang thực đơn (Hero)"));
children.push(body([
  t("Ở đầu trang thực đơn, banner là "),
  t("ảnh không gian quán cỡ lớn", { bold: true }),
  t(" phủ lớp gradient tối, kết hợp lời chào theo buổi (“Chào buổi sáng!”), nhãn bàn đang phục vụ, vị trí bàn và trạng thái “Đơn mới”. Banner sử dụng hiệu ứng overlay mờ và font tiêu đề lớn để làm nổi bật thông tin, tạo ấn tượng mạnh với khách ngay khi quét mã QR truy cập."),
]));
children.push(...figure(
  "Banner (hero) trang thực đơn với ảnh không gian quán và nhãn bàn",
  "Banner trang thực đơn của giao diện khách hàng"));

// 3. Thông tin món + 3D
children.push(h3(3, "Thông tin món và trình xem mô hình 3D"));
children.push(body([
  t("Mỗi món được trình bày dưới dạng "),
  t("thẻ (card)", { bold: true }),
  t(" gồm ảnh, tên món, mô tả ngắn, giá bán và danh sách topping kèm bảng giá. Với các món có dựng mô hình, hệ thống cung cấp nút "),
  t("“Xem 3D”", { bold: true }),
  t(" mở trình xem mô hình ba chiều (dựng bằng Three.js) cho phép xoay, phóng to ly nước với nền trong suốt, đổ bóng mềm và hiệu ứng chuyển động nhẹ. Thông tin được chia thành thẻ và popup tùy chọn, giúp khách dễ đọc và tham khảo trước khi quyết định đặt món."),
]));
children.push(...figure(
  "Thẻ thông tin món và nút Xem 3D",
  "Thẻ thông tin món trong thực đơn"));
children.push(...figure(
  "Trình xem mô hình 3D của ly nước (Three.js) với nền trong suốt và đổ bóng",
  "Trình xem mô hình 3D sản phẩm"));

// 4. So do ban
children.push(h3(4, "Sơ đồ bàn và chức năng chọn / đổi bàn"));
children.push(body([
  t("Thành phần này thay cho “lịch chiếu” trong các hệ thống đặt chỗ: hệ thống hiển thị "),
  t("sơ đồ bàn 3D của quán", { bold: true }),
  t(" theo từng tầng/khu vực. Mỗi bàn là một ghim có màu thể hiện trạng thái: "),
  t("trống, có khách, đặt trước, đang chọn", { bold: true }),
  t(". Khách chạm vào ghim để xem ảnh bàn, số ghế và trạng thái; có thể kéo để xoay, cuộn để phóng to. Khi khách muốn đổi sang bàn khác, hệ thống tạo "),
  t("yêu cầu đổi bàn chờ nhân viên duyệt", { bold: true }),
  t(" để tránh trùng chỗ. Đây cũng là bước “chọn vị trí” trước khi đặt món."),
]));
children.push(...figure(
  "Sơ đồ bàn 3D của quán với chú thích màu trạng thái và panel chọn tầng",
  "Sơ đồ bàn 3D và chức năng chọn / đổi bàn"));

// 5. Dat mon
children.push(h3(5, "Chức năng đặt món"));
children.push(body([
  t("Giao diện đặt món trực quan, các món được phân theo "),
  t("danh mục (tab)", { bold: true }),
  t(". Khi chọn một món, hệ thống mở "),
  t("popup tùy chọn", { bold: true }),
  t(" cho phép chọn mức nóng/lạnh, thêm topping và ghi chú; mỗi tùy chọn được cộng giá tự động. Cùng một món nhưng khác topping sẽ được tách thành "),
  t("dòng riêng trong giỏ hàng", { bold: true }),
  t(". Hệ thống tự tính tổng tiền và cho phép khách chọn "),
  t("hình thức phục vụ: uống tại bàn hoặc mang về (cốc nhựa)", { bold: true }),
  t(". Nút “Xác nhận” và “Thanh toán” nổi bật, xác nhận lại thông tin trước khi chuyển sang bước thanh toán (tiền mặt hoặc VNPay)."),
]));
children.push(...figure(
  "Popup tùy chọn món: chọn nóng/lạnh, topping và ghi chú",
  "Popup tùy chọn món khi đặt"));
children.push(...figure(
  "Giỏ hàng và màn hình thanh toán với lựa chọn hình thức phục vụ",
  "Giỏ hàng và màn hình thanh toán"));

// 6. AI goi y
children.push(h3(6, "Gợi ý thông minh và phân tích dữ liệu (AI)"));
children.push(body([
  t("Thay cho phần “đánh giá & bình luận” ở các hệ thống khác, 8AM Coffee tích hợp "),
  t("gợi ý món mua kèm", { bold: true }),
  t(" hiển thị ngay tại màn hình thanh toán dưới dạng các chip nhỏ mang tính bổ trợ, dựa trên phân tích luật kết hợp (market-basket) từ lịch sử đơn hàng. Phía quản trị có "),
  t("trang Phân tích AI", { bold: true }),
  t(" với dự báo doanh thu, luật kết hợp theo số đơn/khoảng thời gian và các biểu đồ trực quan (Chart.js), kèm hộp hướng dẫn sử dụng. Tính năng này giúp tăng giá trị đơn hàng và hỗ trợ ra quyết định kinh doanh."),
]));
children.push(...figure(
  "Trang Phân tích AI: dự báo doanh thu, gợi ý món mua kèm và biểu đồ thống kê",
  "Trang phân tích dữ liệu thông minh (AI)"));

// 7. Footer
children.push(h3(7, "Chân trang (Footer)"));
children.push(body([
  t("Luôn xuất hiện cuối trang, chứa thông tin liên hệ (địa chỉ, email, hotline), liên kết mạng xã hội, dòng bản quyền và các liên kết nhanh tới chính sách bảo mật, điều khoản sử dụng, câu hỏi thường gặp. Footer dùng nền tối, font nhỏ gọn, tạo cảm giác chuyên nghiệp và tin cậy."),
]));
children.push(...figure(
  "Chân trang (footer) với thông tin liên hệ và liên kết nhanh",
  "Chân trang (Footer) của hệ thống"));

// Phu tro
children.push(h2("Các thành phần phụ trợ"));
children.push(body([
  t("Bên cạnh các thành phần chính, giao diện còn có các thành phần phụ trợ như: "),
  t("modal xác nhận đặt món, popup thông báo thành công/thất bại (toast), thanh tiến trình đặt món", { bold: true }),
  t(" hiển thị các bước “quét QR – chọn món – xác nhận – thanh toán – hoàn tất”, "),
  t("badge trạng thái đơn", { bold: true }),
  t(" (chờ xác nhận, đang pha chế, đã phục vụ, đã thanh toán), bảng giá topping, tooltip hướng dẫn, "),
  t("popup hướng dẫn dùng mô hình 3D để chọn bàn", { bold: true }),
  t(" và hộp hướng dẫn sử dụng phân tích AI. Riêng phía quản trị còn có "),
  t("banner “yêu cầu đổi bàn chờ duyệt”", { bold: true }),
  t(" tự động cập nhật để nhân viên duyệt hoặc từ chối."),
]));
children.push(...figure(
  "Thanh tiến trình đặt món (quét QR – chọn món – xác nhận – thanh toán – hoàn tất)",
  "Thanh tiến trình các bước đặt món"));
children.push(...figure(
  "Bảng điều phối đơn (Order board) kèm sơ đồ bàn và banner duyệt đổi bàn ở giao diện quản trị",
  "Bảng điều phối đơn hàng phía quản trị"));

children.push(body([
  t("Tất cả các thành phần đều được thiết kế "),
  t("đồng bộ về màu sắc, font chữ và bố cục", { bold: true }),
  t(" theo bộ nhận diện 8AM Coffee (đỏ #E82C2A / #BB0011, nâu #522C25, xanh #52613B), không sử dụng biểu tượng cảm xúc (emoji) trong giao diện nhằm giữ tính chuyên nghiệp, đảm bảo nhận diện thương hiệu và trải nghiệm người dùng nhất quán trên mọi thiết bị, hỗ trợ tốt cho cả desktop lẫn mobile."),
]));

// ================= DOCUMENT =================
const doc = new Document({
  styles: {
    default: { document: { run: { font: FONT, size: SZ } } },
    paragraphStyles: [
      { id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 30, bold: true, font: FONT, color: BROWN },
        paragraph: { spacing: { before: 240, after: 160 }, outlineLevel: 1 } },
    ],
  },
  numbering: {
    config: [
      { reference: "ui-bullets",
        levels: [{ level: 0, format: LevelFormat.BULLET, text: "–", alignment: AlignmentType.LEFT,
          style: { run: { font: FONT }, paragraph: { indent: { left: 540, hanging: 280 } } } }] },
    ],
  },
  sections: [{
    properties: {
      page: {
        size: { width: 11906, height: 16838 }, // A4
        margin: { top: 1418, right: 1134, bottom: 1418, left: 1701 }, // lề chuẩn báo cáo VN
      },
    },
    children,
  }],
});

Packer.toBuffer(doc).then((buf) => {
  fs.writeFileSync(process.argv[2], buf);
  console.log("WROTE", process.argv[2], buf.length, "bytes,", figNo, "figures");
});
