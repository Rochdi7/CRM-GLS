// index.tsx
import  { useEffect, useState } from "react";
import { Table } from "antd";
import type { DatatableProps } from "../../data/interface"; // Ensure correct path
 // Ensure correct path


const Datatable: React.FC<DatatableProps> = ({ columns, dataSource , Selection }) => {
  const [selectedRowKeys, setSelectedRowKeys] = useState<any[]>([]);
  const [searchText, setSearchText] = useState<string>("");
  const [Selections, setSelections] = useState<any>(true);
  const [filteredDataSource, setFilteredDataSource] = useState(dataSource);

  const onSelectChange = (newSelectedRowKeys: any[]) => {
    setSelectedRowKeys(newSelectedRowKeys);
  };

  const handleSearch = (value: string) => {
    setSearchText(value);
    const filteredData = dataSource.filter((record) =>
      Object.values(record).some((field) =>
        String(field).toLowerCase().includes(value.toLowerCase())
      )
    );
    setFilteredDataSource(filteredData);
  };

  const rowSelection = {
    selectedRowKeys,
    onChange: onSelectChange,
  };
  useEffect(() => {
    return setSelections(Selection);
  }, [Selection])
  
  
  return (
    <>
     <div className="table-top-data d-flex px-3 justify-content-between">
      <div className="page-range">
      </div>
      <div className="serch-global text-right">
        <input type="search" className="form-control form-control-sm mb-3 w-auto float-end" value={searchText} placeholder="Search" onChange={(e) => handleSearch(e.target.value)} aria-controls="DataTables_Table_0"></input>
      </div>
     </div>
     {!Selections ?
      <Table
      className="table datanew dataTable no-footer"
     
      columns={columns}
      rowHoverable={false}
      dataSource={filteredDataSource}
      pagination={{
        locale: { items_per_page: "" },
        nextIcon: <span>Next</span>,
        prevIcon: <span>Prev</span>,
        defaultPageSize: 10,
        showSizeChanger: true,
        pageSizeOptions: ["10", "20", "30"],
      }}
    /> : 
    <Table
        className="table datanew dataTable no-footer"
        rowSelection={rowSelection}
        columns={columns}
        rowHoverable={false}
        dataSource={filteredDataSource}
        pagination={{
          locale: { items_per_page: "" },
          nextIcon: <span>Next</span>,
          prevIcon: <span>Prev</span>,
          defaultPageSize: 10,
          showSizeChanger: true,
          pageSizeOptions: ["10", "20", "30"],
        }}
      />}
      
    </>
  );
};

export default Datatable;
