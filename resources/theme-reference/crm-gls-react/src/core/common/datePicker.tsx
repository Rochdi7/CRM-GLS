import React, { useState, useRef } from 'react';
import { DatePicker, Dropdown, Menu, Input } from 'antd';
import dayjs from 'dayjs';
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore';
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import localeData from 'dayjs/plugin/localeData';

dayjs.extend(isSameOrBefore);
dayjs.extend(isSameOrAfter);
dayjs.extend(customParseFormat);
dayjs.extend(localeData);

const { RangePicker } = DatePicker;
const dateFormat = 'YYYY/MM/DD';

const PredefinedDatePicker: React.FC = () => {
  const [dates, setDates] = useState<[dayjs.Dayjs, dayjs.Dayjs]>([
    dayjs().subtract(6, 'days'),
    dayjs(),
  ]);
  const [customVisible, setCustomVisible] = useState(false);
  const rangeRef = useRef<any>(null);

  const predefinedRanges: Record<string, [dayjs.Dayjs, dayjs.Dayjs]> = {
    Today: [dayjs(), dayjs()],
    Yesterday: [dayjs().subtract(1, 'day'), dayjs().subtract(1, 'day')],
    'Last 7 Days': [dayjs().subtract(6, 'day'), dayjs()],
    'Last 30 Days': [dayjs().subtract(29, 'day'), dayjs()],
    'This Month': [dayjs().startOf('month'), dayjs().endOf('month')],
    'Last Month': [
      dayjs().subtract(1, 'month').startOf('month'),
      dayjs().subtract(1, 'month').endOf('month'),
    ],
  };

  const handleMenuClick = ({ key }: { key: string }) => {
    if (key === 'Custom Range') {
      setCustomVisible(true);
      // Trigger calendar popup manually
      setTimeout(() => rangeRef.current?.focus(), 0);
    } else {
      setDates(predefinedRanges[key]);
      setCustomVisible(false);
    }
  };

  const handleCustomChange = (value: any) => {
    if (value) {
      setDates(value);
      setCustomVisible(false);
    }
  };

  const menu = (
    <Menu
      onClick={handleMenuClick}
      items={[
        ...Object.keys(predefinedRanges).map(label => ({
          key: label,
          label,
        })),
        { type: 'divider' },
        { key: 'Custom Range', label: 'Custom Range' },
      ]}
    />
  );

  const displayValue = `${dates[0].format(dateFormat)} - ${dates[1].format(dateFormat)}`;

  return (
    <div className='custome-range-picker'>
      <i className='ti ti-calendar'></i>
      <Dropdown overlay={menu} trigger={['click']}>
        <Input
          readOnly
          value={displayValue}
          className=""
        />
      </Dropdown>

      {/* Hidden RangePicker - purely for calendar popup */}
      {customVisible && (
        <RangePicker
          open
          ref={rangeRef}
          onChange={handleCustomChange}
          format={dateFormat}
          value={dates}
          allowClear={false}
          style={{ position: 'absolute', top: 0, left: 0, opacity: 0, pointerEvents: 'none' }}
          onOpenChange={(open) => {
            if (!open) setCustomVisible(false);
          }}
        />
      )}
    </div>
  );
};

export default PredefinedDatePicker;
