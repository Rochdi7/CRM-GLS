import  { useState } from "react";
import ImageWithBasePath from "./imageWithBasePath";
import { Dropdown } from "primereact/dropdown";

export const SelectWithImage = () => {
  const options = [
    {
      label: "Jerald",
      value: "avatar-14.jpg",
      image: "assets/img/profiles/avatar-14.jpg",
    },
    {
      label: "Guillory",
      value: "avatar-15.jpg",
      image: "assets/img/profiles/avatar-15.jpg",
    },
    {
      label: "Jami",
      value: "avatar-16.jpg",
      image: "assets/img/profiles/avatar-16.jpg",
    },
    {
      label: "Theresa",
      value: "avatar-13.jpg",
      image: "assets/img/profiles/avatar-13.jpg",
    },
    {
      label: "Espinosa",
      value: "avatar-24.jpg",
      image: "assets/img/profiles/avatar-24.jpg",
    },
  ];
  const [selectedOption, setSelectedOption] = useState(options[0]);

  const customOptionTemplate = (option: any) => (
    <div className="p-clearfix">
      <ImageWithBasePath alt={option.label} src={option.image} />
      <span>{option.label}</span>
    </div>
  );

  const customValueTemplate = (option: any) => {
    if (!option) {
      return <span>{"Choose Owner"}</span>;
    }
    return (
      <div className="p-clearfix">
        <ImageWithBasePath alt={option.label} src={option.image} />
        <span>{option.label}</span>
      </div>
    );
  };
  return (
    <div>
      <Dropdown
        value={selectedOption}
        options={options}
        optionLabel="label"
        optionValue="value"
        itemTemplate={customOptionTemplate}
        valueTemplate={customValueTemplate}
        onChange={(e) => setSelectedOption(e.value)}
        style={{ width: "100%" }}
        placeholder="Choose Owner"
      />
    </div>
  );
};
